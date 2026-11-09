<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\FilesModel;
use Contao\Model\Registry;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\Widget;

/**
 * CRUD für Catalog-Manager-Konfiguration, Frontend-Ausgabe und Katalogdaten.
 *
 * Alles läuft über das DCA der jeweiligen Tabelle: Feldprüfung, Paletten,
 * save_callback, onbeforesubmit_callback, onsubmit_callback, oncreate_callback
 * und ondelete_callback werden in derselben Reihenfolge wie im Backend
 * (DC_Table) ausgeführt.
 */
final class DcaRecordManager
{
    public const CONFIG_TABLES = ['tl_catalog', 'tl_catalog_field', 'tl_catalog_option', 'tl_catalog_palette'];

    /** Erlaubte Typen in tl_module / tl_content (nur Catalog-Manager-Ausgabe). */
    public const OUTPUT_TYPES = [
        'tl_module' => ['listing-table', 'master', 'listing-map'],
        'tl_content' => ['listview'],
    ];

    /** Felder, die nie direkt gesetzt werden dürfen. */
    private const PROTECTED_FIELDS = ['id', 'tstamp'];

    /** Felder, die außerhalb der Palette gesetzt werden dürfen. */
    private const SYSTEM_FIELDS = ['pid', 'ptable', 'sorting'];

    private const MAX_OPTIONS = 300;

    /** Tabellen, deren onload_callback die Palette eines Datensatzes erweitert. */
    private const ONLOAD_TABLES = ['tl_catalog_palette'];

    private array $pristineDca = [];

    public function __construct(
        private readonly string $projectDir,
        private readonly string $language = 'de',
    )
    {
    }

    // ------------------------------------------------------------------
    // Übersicht & Beschreibung
    // ------------------------------------------------------------------

    public function overview(): array
    {
        $this->resetCaches();
        $db = Database::getInstance();
        $catalogs = [];

        $result = $db->execute('SELECT * FROM tl_catalog ORDER BY pid, sorting');

        while ($result->next()) {
            $row = $result->row();
            $table = (string)($row['table'] ?? '');

            $fields = $db->prepare('SELECT id, name, fieldname, type, role, published FROM tl_catalog_field WHERE pid=? ORDER BY sorting')->execute($row['id'])->fetchAllAssoc();

            $catalogs[] = [
                'id' => (int)$row['id'],
                'pid' => (int)$row['pid'],
                'name' => $row['name'] ?? '',
                'type' => $row['type'] ?? '',
                'table' => $table,
                'mode' => $row['mode'] ?? '',
                'navigation' => $row['navigation'] ?? '',
                'tableExists' => '' !== $table && $db->tableExists($table, null, true),
                'entries' => ('' !== $table && $db->tableExists($table, null, true)) ? (int)$db->execute('SELECT COUNT(*) AS c FROM ' . $table)->c : null,
                'fields' => $fields,
            ];
        }

        $modules = $db->execute("SELECT id, pid, name, type, cmTable, cmTemplate, customTpl FROM tl_module WHERE type IN ('" . implode("','", self::OUTPUT_TYPES['tl_module']) . "')")->fetchAllAssoc();
        $elements = $db->execute("SELECT id, pid, ptable, type, cmTable, customTpl FROM tl_content WHERE type IN ('" . implode("','", self::OUTPUT_TYPES['tl_content']) . "')")->fetchAllAssoc();

        return [
            'catalogs' => $catalogs,
            'frontendModules' => $modules,
            'contentElements' => $elements,
            'editableTables' => $this->allowedTables(),
        ];
    }

    /**
     * Beschreibt die Konfigurationsmöglichkeiten einer Tabelle.
     *
     * @param array $context Werte, nach denen die Palette gewählt wird (z. B. ['type' => 'select'])
     */
    public function describe(string $table, array $context = [], ?int $id = null): array
    {
        $kind = $this->assertTable($table);
        $dca = $this->loadDca($table);

        $values = $this->defaultValues($table);

        if (null !== $id) {
            $values = array_merge($values, $this->fetchRow($table, $id));
        }

        $values = array_merge($values, $this->normalizeInput($context));

        $record = new RecordStub($values);

        if (null !== $id) {
            $dca = $this->runOnload($table, $id, $record);
        }

        $dc = new HeadlessDataContainer($table, $id, $record);
        $active = PaletteResolver::resolve($dca, $values);
        $dc->setPalette($active['palette']);

        $fields = [];

        foreach ($active['fields'] as $name) {
            if (!isset($dca['fields'][$name])) {
                continue;
            }

            $fields[] = $this->describeField($table, $name, $dca['fields'][$name], $dc);
        }

        $selectors = $dca['palettes']['__selector__'] ?? [];

        $result = [
            'table' => $table,
            'kind' => $kind,
            'parentTable' => $dca['config']['ptable'] ?? null,
            'childTables' => $dca['config']['ctable'] ?? [],
            'selectors' => $selectors,
            'availablePalettes' => PaletteResolver::paletteKeys($dca),
            'activePalette' => $active['key'],
            'context' => $context,
            'fields' => $fields,
            'otherFields' => array_values(array_diff(
                array_keys(array_filter($dca['fields'] ?? [], static fn($f) => isset($f['inputType']))),
                $active['fields']
            )),
            'hint' => 'Die Feldliste hängt von den Selektoren ab (meist "type"). Mit context={"type": "..."} eine andere Palette beschreiben.',
        ];

        if (isset(self::OUTPUT_TYPES[$table])) {
            $result['allowedTypes'] = self::OUTPUT_TYPES[$table];
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Lesen
    // ------------------------------------------------------------------

    public function list(string $table, array $where = [], ?string $orderBy = null, int $limit = 50, int $offset = 0, array $columns = []): array
    {
        $this->assertTable($table);
        $dca = $this->loadDca($table);
        $db = Database::getInstance();

        $conditions = [];
        $params = [];

        foreach ($this->normalizeInput($where) as $field => $value) {
            $this->assertColumn($table, $field);
            $conditions[] = $field . '=?';
            $params[] = \is_bool($value) ? ($value ? '1' : '') : $value;
        }

        if (isset(self::OUTPUT_TYPES[$table])) {
            $conditions[] = "type IN ('" . implode("','", self::OUTPUT_TYPES[$table]) . "')";
        }

        $select = '*';

        if ($columns) {
            foreach ($columns as $column) {
                $this->assertColumn($table, (string)$column);
            }
            $select = implode(', ', array_unique(array_merge(['id'], $columns)));
        }

        $sql = 'SELECT ' . $select . ' FROM ' . $table;

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($orderBy) {
            if (!preg_match('/^([a-zA-Z0-9_]+)(\s+(ASC|DESC))?$/i', trim($orderBy), $m)) {
                throw new \InvalidArgumentException('orderBy muss "feld" oder "feld ASC|DESC" sein.');
            }
            $this->assertColumn($table, $m[1]);
            $sql .= ' ORDER BY ' . trim($orderBy);
        } elseif (isset($dca['fields']['sorting'])) {
            $sql .= ' ORDER BY sorting';
        }

        $limit = max(1, min($limit, 500));
        $total = (int)$db->prepare('SELECT COUNT(*) AS c FROM ' . $table . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : ''))->execute(...$params)->c;
        $rows = $db->prepare($sql)->limit($limit, max(0, $offset))->execute(...$params)->fetchAllAssoc();

        return [
            'table' => $table,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'records' => array_map(fn($row) => $this->normalizeOutput($table, $row), $rows),
        ];
    }

    public function get(string $table, int $id): array
    {
        $this->assertTable($table);
        $this->loadDca($table);
        $row = $this->fetchRow($table, $id);
        $this->assertOutputType($table, $row['type'] ?? null);

        return $this->normalizeOutput($table, $row);
    }

    // ------------------------------------------------------------------
    // Schreiben
    // ------------------------------------------------------------------

    public function create(string $table, array $values, ?int $pid = null, ?string $ptable = null): array
    {
        $this->assertTable($table);
        $dca = $this->loadDca($table);
        $values = $this->normalizeInput($values);
        $db = Database::getInstance();

        if (isset(self::OUTPUT_TYPES[$table])) {
            $this->assertOutputType($table, $values['type'] ?? null, true);
        }

        $set = [];

        foreach ($this->defaultValues($table) as $field => $default) {
            $set[$field] = $this->toDbValue($table, $field, $default);
        }

        $configPtable = $dca['config']['ptable'] ?? null;
        $dynamicPtable = !empty($dca['config']['dynamicPtable']);

        if ($pid === null && isset($values['pid'])) {
            $pid = (int)$values['pid'];
        }
        unset($values['pid']);

        if ($dynamicPtable) {
            $ptable = $ptable ?? ($values['ptable'] ?? $configPtable);
            if (!$ptable) {
                throw new \InvalidArgumentException(sprintf('Für %s muss "ptable" angegeben werden (z. B. tl_article).', $table));
            }
            $set['ptable'] = $ptable;
        }
        unset($values['ptable']);

        if (($configPtable || $dynamicPtable) && !$pid) {
            throw new \InvalidArgumentException(sprintf('Für %s ist "pid" (ID des Elterndatensatzes in %s) erforderlich.', $table, $ptable ?? $configPtable));
        }

        if ($db->fieldExists('pid', $table, true)) {
            $set['pid'] = $pid ?? 0;
        }

        if ($db->fieldExists('sorting', $table, true) && !isset($values['sorting'])) {
            $max = $db->prepare('SELECT MAX(sorting) AS s FROM ' . $table . ($db->fieldExists('pid', $table, true) ? ' WHERE pid=?' : ''))->execute($set['pid'] ?? 0)->s;
            $set['sorting'] = (int)$max + 128;
        }

        if ($db->fieldExists('tstamp', $table, true)) {
            $set['tstamp'] = 0;
        }

        $parentTable = $set['ptable'] ?? $configPtable;
        if ($pid && $parentTable && preg_match('/^[a-z0-9_]+$/i', $parentTable) && $db->tableExists($parentTable, null, true)
            && !$db->prepare('SELECT id FROM ' . $parentTable . ' WHERE id=?')->execute($pid)->numRows) {
            throw new \InvalidArgumentException(sprintf('Elterndatensatz %d in %s existiert nicht.', $pid, $parentTable));
        }

        // Feldnamen und Palette prüfen, bevor etwas in die Datenbank geschrieben wird
        // (Tabellen mit datensatzabhängigen Paletten werden nach dem Anlegen geprüft)
        $this->checkValues($table, $values, $set, !\in_array($table, self::ONLOAD_TABLES, true));

        $id = (int)$db->prepare('INSERT INTO ' . $table . ' %s')->set($set)->execute()->insertId;

        try {
            $record = new RecordStub($this->fetchRow($table, $id));
            $dc = new HeadlessDataContainer($table, $id, $record);

            foreach ($dca['config']['oncreate_callback'] ?? [] as $callback) {
                $this->call($callback, [$table, $id, $set, $dc]);
            }

            $changed = $this->applyValues($table, $id, $values, true);
        } catch (\Throwable $e) {
            $db->prepare('DELETE FROM ' . $table . ' WHERE id=?')->execute($id);

            throw new \RuntimeException('Anlegen fehlgeschlagen, Datensatz wurde zurückgenommen: ' . $e->getMessage(), 0, $e);
        }

        return [
            'created' => true,
            'id' => $id,
            'changedFields' => $changed,
            'record' => $this->normalizeOutput($table, $this->fetchRow($table, $id)),
        ];
    }

    public function update(string $table, int $id, array $values): array
    {
        $this->assertTable($table);
        $this->loadDca($table);
        $row = $this->fetchRow($table, $id);
        $this->assertOutputType($table, $row['type'] ?? null);

        $values = $this->normalizeInput($values);

        if (isset(self::OUTPUT_TYPES[$table]) && isset($values['type'])) {
            $this->assertOutputType($table, $values['type'], true);
        }

        $changed = $this->applyValues($table, $id, $values, false);

        return [
            'updated' => true,
            'id' => $id,
            'changedFields' => $changed,
            'record' => $this->normalizeOutput($table, $this->fetchRow($table, $id)),
        ];
    }

    public function delete(string $table, int $id): array
    {
        $this->assertTable($table);
        $dca = $this->loadDca($table);
        $db = Database::getInstance();
        $row = $this->fetchRow($table, $id);
        $this->assertOutputType($table, $row['type'] ?? null);

        // Baumansicht (z. B. tl_catalog): Unterknoten nicht stillschweigend mitlöschen
        if (5 === (int)($dca['list']['sorting']['mode'] ?? 0)) {
            $children = (int)$db->prepare('SELECT COUNT(*) AS c FROM ' . $table . ' WHERE pid=?')->execute($id)->c;
            if ($children > 0) {
                throw new \RuntimeException(sprintf('Datensatz %d hat %d untergeordnete Einträge in %s. Bitte diese zuerst löschen oder verschieben.', $id, $children, $table));
            }
        }

        $backup = ['table' => $table, 'record' => $row, 'children' => $this->collectChildren($table, $id)];
        $backupFile = $this->writeBackup($table, $id, $backup);

        $dc = new HeadlessDataContainer($table, $id, new RecordStub($row));

        foreach ($dca['config']['ondelete_callback'] ?? [] as $callback) {
            $this->call($callback, [$dc, 0]);
        }

        $deletedChildren = $this->deleteChildren($table, $id);
        $db->prepare('DELETE FROM ' . $table . ' WHERE id=?')->execute($id);

        return [
            'deleted' => true,
            'id' => $id,
            'deletedChildren' => $deletedChildren,
            'backup' => $backupFile,
        ];
    }

    // ------------------------------------------------------------------
    // Interne Logik
    // ------------------------------------------------------------------

    /**
     * Führt die Felder wie DC_Table::save() durch die Callbacks und speichert.
     *
     * @return list<string> geänderte Felder
     */
    private function applyValues(string $table, int $id, array $values, bool $isNew): array
    {
        $record = new RecordStub($this->fetchRow($table, $id));
        $dca = $this->runOnload($table, $id, $record);
        $fieldsCfg = $dca['fields'] ?? [];
        $db = Database::getInstance();

        $dc = new HeadlessDataContainer($table, $id, $record);

        [$dbValues, $active] = $this->checkValues($table, $values, $record->row());
        $dc->setPalette($active['palette']);

        // Selektoren zuerst setzen, damit Callbacks den neuen Typ kennen (wie im Backend nach dem Typwechsel)
        foreach ($dca['palettes']['__selector__'] ?? [] as $selector) {
            if (\array_key_exists($selector, $dbValues) && empty($fieldsCfg[$selector]['save_callback'])) {
                $record->{$selector} = StringUtil::deserialize($dbValues[$selector]);
            }
        }

        $submit = [];

        foreach (array_intersect(self::SYSTEM_FIELDS, array_keys($dbValues)) as $field) {
            $submit[$field] = $dbValues[$field];
        }

        foreach ($active['fields'] as $field) {
            $cfg = $fieldsCfg[$field] ?? null;

            if (!\is_array($cfg) || !isset($cfg['inputType'])) {
                continue;
            }

            $submitted = \array_key_exists($field, $dbValues);
            $hasCallbacks = !empty($cfg['save_callback']);

            // Im Backend wird jedes Feld der Palette gespeichert; ohne Callback und ohne Änderung gibt es nichts zu tun.
            if (!$submitted && !$hasCallbacks && !$isNew) {
                continue;
            }

            $current = $this->rawValue($table, $id, $field, $record);
            $value = $submitted ? $dbValues[$field] : $current;

            if (($cfg['eval']['mandatory'] ?? false) && $this->isEmpty($value) && !$hasCallbacks) {
                throw new \InvalidArgumentException(sprintf('Pflichtfeld "%s" (%s) fehlt.', $field, $this->label($table, $field)));
            }

            if ($submitted) {
                $this->validateValue($table, $field, $cfg, $value);
            }

            $dc->setField($field, StringUtil::deserialize($current));

            foreach ($cfg['save_callback'] ?? [] as $callback) {
                $value = $this->call($callback, [$value, $dc]);
            }

            if (($cfg['eval']['mandatory'] ?? false) && $this->isEmpty($value)) {
                throw new \InvalidArgumentException(sprintf('Pflichtfeld "%s" (%s) ist leer.', $field, $this->label($table, $field)));
            }

            if (($cfg['eval']['unique'] ?? false) && !$this->isEmpty($value) && !$db->isUniqueValue($table, $field, $value, $id)) {
                throw new \InvalidArgumentException(sprintf('Der Wert für "%s" ist bereits vergeben.', $field));
            }

            if (!\is_array($value) && '' === (string)$value) {
                if ($cfg['eval']['doNotSaveEmpty'] ?? false) {
                    continue;
                }
                $value = Widget::getEmptyValueByFieldType($cfg['sql'] ?? []);
            }

            if ($isNew || $submitted || (string)$value !== (string)$current) {
                if ($isNew || (string)$value !== (string)$current || ($cfg['eval']['alwaysSave'] ?? false)) {
                    $submit[$field] = $value;
                }
                $record->{$field} = StringUtil::deserialize($value);
            }
        }

        foreach ($dca['config']['onbeforesubmit_callback'] ?? [] as $callback) {
            $returned = $this->call($callback, [$submit, $dc]);
            if (!\is_array($returned)) {
                throw new \RuntimeException('Der onbeforesubmit_callback muss die Werte zurückgeben.');
            }
            $submit = $returned;
        }

        if ($submit) {
            if ($db->fieldExists('tstamp', $table, true)) {
                $submit['tstamp'] = time();
            }
            $db->prepare('UPDATE ' . $table . ' %s WHERE id=?')->set($submit)->execute($id);

            foreach ($submit as $field => $value) {
                $record->{$field} = StringUtil::deserialize($value);
            }
        } elseif ($isNew && $db->fieldExists('tstamp', $table, true)) {
            $db->prepare('UPDATE ' . $table . ' SET tstamp=? WHERE id=?')->execute(time(), $id);
        }

        $dc->setField(null);

        foreach ($dca['config']['onsubmit_callback'] ?? [] as $callback) {
            $this->call($callback, [$dc]);
        }

        return array_values(array_diff(array_keys($submit), ['tstamp']));
    }

    /**
     * Prüft Feldnamen und Palette, bevor irgendetwas geschrieben wird.
     *
     * @return array{0: array, 1: array} Werte im DB-Format und aktive Palette
     */
    private function checkValues(string $table, array $values, array $baseRow, bool $checkPalette = true): array
    {
        $dca = $GLOBALS['TL_DCA'][$table];
        $fieldsCfg = $dca['fields'] ?? [];

        foreach (array_keys($values) as $field) {
            if (\in_array($field, self::PROTECTED_FIELDS, true)) {
                throw new \InvalidArgumentException(sprintf('Das Feld "%s" wird automatisch gesetzt.', $field));
            }
            if (!isset($fieldsCfg[$field])) {
                throw new \InvalidArgumentException(sprintf('Unbekanntes Feld "%s" in %s. Mögliche Felder liefert config_describe.', $field, $table));
            }
        }

        // Palette anhand der neuen Werte bestimmen (Typwechsel ändert die Felder)
        $dbValues = [];
        foreach ($values as $field => $value) {
            $dbValues[$field] = $this->toDbValue($table, $field, $value);
        }

        $merged = array_merge($baseRow, array_map(static fn($v) => StringUtil::deserialize($v), $dbValues));
        $active = PaletteResolver::resolve($dca, $merged);

        $outside = $checkPalette ? array_diff(array_keys($values), $active['fields'], self::SYSTEM_FIELDS) : [];
        if ($outside) {
            throw new \InvalidArgumentException(sprintf(
                'Die Felder %s gehören nicht zur aktiven Palette "%s". Aktive Felder: %s',
                implode(', ', $outside),
                $active['key'],
                implode(', ', $active['fields'])
            ));
        }

        return [$dbValues, $active];
    }

    private function rawValue(string $table, int $id, string $field, RecordStub $record): mixed
    {
        $value = $record->{$field};

        return \is_array($value) ? serialize($value) : $value;
    }

    private function validateValue(string $table, string $field, array $cfg, mixed $value): void
    {
        if (\is_array($value) || null === $value || '' === (string)$value) {
            return;
        }

        $maxlength = (int)($cfg['eval']['maxlength'] ?? 0);
        if ($maxlength > 0 && mb_strlen((string)$value) > $maxlength && \in_array($cfg['inputType'], ['text', 'textarea'], true)) {
            throw new \InvalidArgumentException(sprintf('"%s" darf höchstens %d Zeichen haben.', $field, $maxlength));
        }

        $rgxp = $cfg['eval']['rgxp'] ?? null;
        $checks = [
            'digit' => static fn($v) => Validator::isNumeric($v),
            'natural' => static fn($v) => Validator::isNatural($v),
            'email' => static fn($v) => Validator::isEmail($v),
            'url' => static fn($v) => Validator::isUrl($v),
            'alias' => static fn($v) => Validator::isAlias($v),
            'extnd' => static fn($v) => Validator::isExtendedAlphanumeric($v),
        ];

        if ($rgxp && isset($checks[$rgxp]) && !$checks[$rgxp]((string)$value)) {
            throw new \InvalidArgumentException(sprintf('Ungültiger Wert für "%s" (erwartet: %s).', $field, $rgxp));
        }
    }

    /**
     * Wandelt einen Eingabewert in das Datenbankformat des Feldes.
     */
    private function toDbValue(string $table, string $field, mixed $value): mixed
    {
        $cfg = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];
        $eval = $cfg['eval'] ?? [];
        $inputType = $cfg['inputType'] ?? null;

        if (\is_bool($value)) {
            return $value ? '1' : '';
        }

        if (null === $value) {
            return Widget::getEmptyValueByFieldType($cfg['sql'] ?? []);
        }

        if ('fileTree' === $inputType) {
            $items = \is_array($value) ? $value : [$value];
            $uuids = [];

            foreach ($items as $item) {
                $item = (string)$item;
                if (Validator::isStringUuid($item)) {
                    $uuids[] = StringUtil::uuidToBin($item);
                } elseif ('' !== $item) {
                    $file = FilesModel::findByPath($item);
                    if (null === $file) {
                        throw new \InvalidArgumentException(sprintf('Datei "%s" für Feld "%s" nicht in der Dateiverwaltung gefunden.', $item, $field));
                    }
                    $uuids[] = $file->uuid;
                }
            }

            if (!empty($eval['multiple'])) {
                return $uuids ? serialize($uuids) : Widget::getEmptyStringOrNullByFieldType($cfg['sql'] ?? []);
            }

            return $uuids[0] ?? null;
        }

        if (\is_array($value)) {
            if (isset($eval['csv'])) {
                return implode($eval['csv'], $value);
            }

            return $value ? serialize($value) : Widget::getEmptyStringOrNullByFieldType($cfg['sql'] ?? []);
        }

        return $value;
    }

    private function normalizeOutput(string $table, array $row): array
    {
        $out = [];

        foreach ($row as $field => $value) {
            $out[$field] = $this->readable(StringUtil::deserialize($value));
        }

        return $out;
    }

    private function readable(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map(fn($v) => $this->readable($v), $value);
        }

        if (\is_string($value) && '' !== $value && !mb_check_encoding($value, 'UTF-8')) {
            return 16 === \strlen($value) ? StringUtil::binToUuid($value) : 'base64:' . base64_encode($value);
        }

        return $value;
    }

    /**
     * MCP-Argumente kommen als JSON; verschachtelte Objekte in Arrays umwandeln.
     */
    private function normalizeInput(array $values): array
    {
        return json_decode(json_encode($values, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    private function describeField(string $table, string $name, array $cfg, HeadlessDataContainer $dc): array
    {
        $eval = $cfg['eval'] ?? [];
        $label = $this->labelArray($table, $name, $cfg);

        $info = [
            'name' => $name,
            'label' => $label[0] ?? $name,
            'help' => $label[1] ?? '',
            'inputType' => $cfg['inputType'] ?? null,
        ];

        foreach (['mandatory', 'multiple', 'unique', 'rgxp', 'maxlength', 'csv', 'isAssociative', 'includeBlankOption'] as $key) {
            if (isset($eval[$key]) && false !== $eval[$key]) {
                $info[$key] = $eval[$key];
            }
        }

        if (\array_key_exists('default', $cfg) && !\is_callable($cfg['default'])) {
            $info['default'] = $cfg['default'];
        }

        if (\in_array($name, $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] ?? [], true)) {
            $info['isSelector'] = true;
        }

        if (!empty($cfg['save_callback'])) {
            $info['hasSaveLogic'] = true;
        }

        $options = $this->resolveOptions($table, $name, $cfg, $dc);
        if (null !== $options) {
            $info['options'] = $options;
        }

        if ('fileTree' === ($cfg['inputType'] ?? null)) {
            $info['valueFormat'] = 'UUID oder Pfad (z. B. files/bilder/foto.jpg); bei multiple ein Array.';
        }

        return $info;
    }

    private function resolveOptions(string $table, string $name, array $cfg, HeadlessDataContainer $dc): ?array
    {
        $options = null;

        try {
            if (isset($cfg['options_callback'])) {
                $dc->setField($name, $dc->getRecord()->{$name});
                $options = $this->call($cfg['options_callback'], [$dc]);
            } elseif (isset($cfg['options']) && \is_array($cfg['options'])) {
                $options = $cfg['options'];
            } elseif (isset($cfg['foreignKey']) && preg_match('/^([a-z0-9_]+)\.([a-z0-9_]+)$/i', (string)$cfg['foreignKey'], $m)) {
                $options = [];
                $rows = Database::getInstance()->execute('SELECT id, ' . $m[2] . ' AS label FROM ' . $m[1] . ' LIMIT ' . self::MAX_OPTIONS)->fetchAllAssoc();
                foreach ($rows as $row) {
                    $options[$row['id']] = $row['label'];
                }
            }
        } catch (\Throwable $e) {
            return [['error' => 'Optionen konnten nicht ermittelt werden: ' . $e->getMessage()]];
        }

        if (!\is_array($options)) {
            return null;
        }

        $reference = $cfg['reference'] ?? [];
        $isAssoc = ($cfg['eval']['isAssociative'] ?? false) || array_is_list($options) === false;
        $list = [];

        foreach ($options as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $k => $v) {
                    $optValue = array_is_list($value) ? $v : $k;
                    $list[] = ['value' => $optValue, 'label' => $this->refLabel($reference, $optValue, $v), 'group' => $this->refLabel($reference, $key, $key)];
                }
                continue;
            }

            $optValue = $isAssoc ? $key : $value;
            $list[] = ['value' => $optValue, 'label' => $this->refLabel($reference, $optValue, $value)];

            if (\count($list) >= self::MAX_OPTIONS) {
                $list[] = ['info' => 'gekürzt'];
                break;
            }
        }

        return $list;
    }

    private function refLabel(array $reference, mixed $key, mixed $fallback): string
    {
        $ref = \is_scalar($key) ? ($reference[$key] ?? null) : null;

        if (\is_array($ref)) {
            $ref = $ref[0] ?? null;
        }

        return \is_scalar($ref) ? (string)$ref : (\is_scalar($fallback) ? (string)$fallback : '');
    }

    private function labelArray(string $table, string $field, array $cfg): array
    {
        $label = $cfg['label'] ?? ($GLOBALS['TL_LANG'][$table][$field] ?? null);

        if (\is_string($label)) {
            return [$label, ''];
        }

        return \is_array($label) ? array_values($label) : [$field, ''];
    }

    private function label(string $table, string $field): string
    {
        return (string)($this->labelArray($table, $field, $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [])[0] ?? $field);
    }

    private function isEmpty(mixed $value): bool
    {
        if (\is_array($value)) {
            return [] === $value;
        }

        if (null === $value || '' === (string)$value) {
            return true;
        }

        $deserialized = StringUtil::deserialize($value);

        return \is_array($deserialized) && [] === array_filter($deserialized, static fn($v) => '' !== $v && null !== $v);
    }

    private function defaultValues(string $table): array
    {
        $defaults = [];

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $field => $cfg) {
            if (\array_key_exists('default', $cfg) && isset($cfg['sql'])) {
                $defaults[$field] = \is_callable($cfg['default']) && !\is_string($cfg['default']) ? ($cfg['default'])() : $cfg['default'];
            }
        }

        return $defaults;
    }

    private function collectChildren(string $table, int $id, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $db = Database::getInstance();
        $children = [];

        foreach ((array)($GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? []) as $ctable) {
            if (!$db->tableExists($ctable, null, true)) {
                continue;
            }
            $this->loadDca($ctable, false);
            $rows = $this->childRows($table, $ctable, $id);
            foreach ($rows as $row) {
                $children[] = ['table' => $ctable, 'record' => $row, 'children' => $this->collectChildren($ctable, (int)$row['id'], $depth + 1)];
            }
        }

        return $children;
    }

    private function deleteChildren(string $table, int $id, int $depth = 0): int
    {
        if ($depth > 5) {
            return 0;
        }

        $db = Database::getInstance();
        $count = 0;

        foreach ((array)($GLOBALS['TL_DCA'][$table]['config']['ctable'] ?? []) as $ctable) {
            if (!$db->tableExists($ctable, null, true)) {
                continue;
            }
            $this->loadDca($ctable, false);
            foreach ($this->childRows($table, $ctable, $id) as $row) {
                $count += $this->deleteChildren($ctable, (int)$row['id'], $depth + 1);
                $db->prepare('DELETE FROM ' . $ctable . ' WHERE id=?')->execute($row['id']);
                ++$count;
            }
        }

        return $count;
    }

    private function childRows(string $table, string $ctable, int $id): array
    {
        $db = Database::getInstance();

        if (!empty($GLOBALS['TL_DCA'][$ctable]['config']['dynamicPtable']) && $db->fieldExists('ptable', $ctable, true)) {
            return $db->prepare('SELECT * FROM ' . $ctable . ' WHERE pid=? AND ptable=?')->execute($id, $table)->fetchAllAssoc();
        }

        return $db->prepare('SELECT * FROM ' . $ctable . ' WHERE pid=?')->execute($id)->fetchAllAssoc();
    }

    private function writeBackup(string $table, int $id, array $data): string
    {
        $dir = $this->projectDir . '/var/catalog-mcp/backups';

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Backup-Verzeichnis konnte nicht angelegt werden: ' . $dir);
        }

        $file = sprintf('%s/%s-%d-%s.json', $dir, $table, $id, date('Ymd-His'));
        $json = json_encode($this->readable($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if (false === file_put_contents($file, $json)) {
            throw new \RuntimeException('Backup konnte nicht geschrieben werden, Löschen abgebrochen.');
        }

        return substr($file, \strlen($this->projectDir) + 1);
    }

    private function fetchRow(string $table, int $id): array
    {
        $result = Database::getInstance()->prepare('SELECT * FROM ' . $table . ' WHERE id=?')->limit(1)->execute($id);

        if (!$result->numRows) {
            throw new \InvalidArgumentException(sprintf('Datensatz %d in %s nicht gefunden.', $id, $table));
        }

        return $result->row();
    }

    /**
     * @return list<string>
     */
    public function allowedTables(): array
    {
        $db = Database::getInstance();
        $data = $db->execute("SELECT `table` FROM tl_catalog WHERE `table`!=''")->fetchEach('table');
        $data = array_values(array_filter($data, static fn($t) => preg_match('/^[a-z0-9_]+$/i', (string)$t) && $db->tableExists($t, null, true)));

        return array_values(array_unique(array_merge(self::CONFIG_TABLES, array_keys(self::OUTPUT_TYPES), $data)));
    }

    /**
     * @return 'config'|'output'|'data'
     */
    private function assertTable(string $table): string
    {
        $this->resetCaches();

        if (\in_array($table, self::CONFIG_TABLES, true)) {
            return 'config';
        }

        if (isset(self::OUTPUT_TYPES[$table])) {
            return 'output';
        }

        if (\in_array($table, $this->allowedTables(), true)) {
            return 'data';
        }

        throw new \InvalidArgumentException(sprintf('Tabelle "%s" ist nicht freigegeben. Erlaubt: %s', $table, implode(', ', $this->allowedTables())));
    }

    private function assertOutputType(string $table, mixed $type, bool $required = false): void
    {
        if (!isset(self::OUTPUT_TYPES[$table])) {
            return;
        }

        if ((null === $type && !$required) || \in_array($type, self::OUTPUT_TYPES[$table], true)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('In %s sind nur Catalog-Manager-Typen erlaubt: %s', $table, implode(', ', self::OUTPUT_TYPES[$table])));
    }

    private function assertColumn(string $table, string $field): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $field) || !Database::getInstance()->fieldExists($field, $table, true)) {
            throw new \InvalidArgumentException(sprintf('Unbekannte Spalte "%s" in %s.', $field, $table));
        }
    }

    private function loadDca(string $table, bool $required = true): array
    {
        System::loadLanguageFile('default', $this->language);
        System::loadLanguageFile($table, $this->language);
        Controller::loadDataContainer($table);

        if ($required && !isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            throw new \RuntimeException(sprintf('Für %s wurde kein DCA gefunden.', $table));
        }

        // Der Server läuft lange: onload_callbacks verändern das DCA pro Datensatz,
        // deshalb vor jeder Aktion den Ursprungszustand wiederherstellen.
        if (isset($GLOBALS['TL_DCA'][$table])) {
            if (!isset($this->pristineDca[$table])) {
                $this->pristineDca[$table] = $GLOBALS['TL_DCA'][$table];
            } else {
                $GLOBALS['TL_DCA'][$table] = $this->pristineDca[$table];
            }
        }

        return $GLOBALS['TL_DCA'][$table] ?? [];
    }

    /**
     * Führt die onload_callbacks der Tabellen aus, deren Paletten vom Datensatz abhängen.
     * Andere onload_callbacks (z. B. Rechteprüfungen des Contao-Cores) brauchen einen
     * Backend-Benutzer und werden bewusst nicht ausgeführt.
     */
    private function runOnload(string $table, int $id, RecordStub $record): array
    {
        if (\in_array($table, self::ONLOAD_TABLES, true)) {
            $dc = new HeadlessDataContainer($table, $id, $record);

            foreach ($GLOBALS['TL_DCA'][$table]['config']['onload_callback'] ?? [] as $callback) {
                $this->call($callback, [$dc]);
            }
        }

        return $GLOBALS['TL_DCA'][$table];
    }

    /**
     * Der Server läuft als langlebiger Prozess: Model-Registry und Datensatz-Cache
     * von Contao vor jeder Aktion leeren, damit Callbacks aktuelle Daten sehen.
     */
    private function resetCaches(): void
    {
        Registry::getInstance()->reset();
        DataContainer::clearCurrentRecordCache();
    }

    private function call(mixed $callback, array $args): mixed
    {
        if (\is_array($callback) && isset($callback[0], $callback[1]) && \is_string($callback[1])) {
            return System::importStatic($callback[0])->{$callback[1]}(...$args);
        }

        if (\is_callable($callback)) {
            return $callback(...$args);
        }

        throw new \RuntimeException('Ungültiger Callback im DCA.');
    }
}
