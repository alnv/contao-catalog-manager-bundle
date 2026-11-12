<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\AbstractRecordEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\FrontendManagerEvents;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordCreatedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordDeletedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordUpdatedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordVisibilityChangedEvent;
use Alnv\ContaoCatalogManagerBundle\Mcp\DcaRecordManager;
use Alnv\ContaoCatalogManagerBundle\Mcp\PaletteResolver;
use Contao\Database;
use Contao\StringUtil;
use Contao\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Alle Datensatzaktionen der Frontend-Verwaltung.
 *
 * Jede öffentliche Methode prüft selbst:
 *  - Modul-, Tabellen- und Aktionsberechtigung (AccessGuard),
 *  - Feldberechtigung (nur freigegebene Felder werden übernommen),
 *  - Datensatzberechtigung (Abfrage mit RecordScope-Bedingungen; eine manipulierte ID findet nichts).
 *
 * Gespeichert wird über den DcaRecordManager, der save_callback, onsubmit_callback,
 * oncreate_callback und ondelete_callback wie DC_Table ausführt. Die Templates und später
 * die AJAX-Endpunkte rufen ausschließlich diese Klasse auf und nutzen damit dieselben Regeln.
 *
 * Nach jeder erfolgreichen Schreibaktion wird ein Event ausgelöst (FrontendManagerEvents), an das
 * Entwickler Benachrichtigungen oder Schnittstellen hängen können.
 */
final class RecordService
{
    private ?array $scopeConditions = null;

    public function __construct(
        private readonly AccessGuard $guard,
        private readonly DcaRecordManager $persister,
        private readonly ?\Closure $replaceInsertTags = null,
        private readonly ?EventDispatcherInterface $dispatcher = null,
        private readonly int $moduleId = 0,
        private readonly ?LoggerInterface $logger = null,
    )
    {
    }

    public function getGuard(): AccessGuard
    {
        return $this->guard;
    }

    // ---------------------------------------------------------------
    // Lesen
    // ---------------------------------------------------------------

    /**
     * Bedingungen der erlaubten Datensatzmenge (für Liste und alle Aktionen).
     *
     * @return array{columns: list<string>, values: list<mixed>}
     */
    public function getScopeConditions(): array
    {
        $this->guard->assertCanUse();

        return $this->scopeConditions ??= $this->createScope()->getConditions();
    }

    /**
     * Optionen für Views\Listing: erlaubte Datenmenge + Frontend-Filter + Sortierung.
     *
     * @param array<string, mixed> $filterInput
     * @return array{options: array, activeFilters: array<string, string>, sort: ?string, direction: string}
     */
    public function getListOptions(array $filterInput = [], ?string $sort = null, ?string $direction = null): array
    {
        $scope = $this->getScopeConditions();
        $userFilter = $this->createScope()->getUserFilterConditions($filterInput, $this->guard->getFilterFields());
        $table = '`' . $this->guard->getConfig()->table . '`';

        $order = [];
        $direction = 'desc' === strtolower((string)$direction) ? 'DESC' : 'ASC';

        if (null !== $sort && \in_array($sort, $this->guard->getSortableFields(), true)) {
            $order[] = $table . '.`' . $sort . '` ' . $direction;
        } else {
            $sort = null;
        }

        foreach ($this->guard->getConfig()->defaultOrder as [$field, $dir]) {
            if ($this->columnExists($field)) {
                $order[] = $table . '.`' . $field . '` ' . $dir;
            }
        }

        $order[] = $table . '.`id` DESC';

        $config = $this->guard->getConfig();

        return [
            'options' => [
                'id' => $config->moduleId,
                'column' => array_merge($scope['columns'], $userFilter['columns']),
                'value' => array_merge($scope['values'], $userFilter['values']),
                'order' => implode(', ', array_unique($order)),
                'limit' => $config->perPage,
                'pagination' => $config->perPage > 0,
                // Besitzer sehen auch ihre unveröffentlichten Einträge
                'ignoreVisibility' => true,
            ],
            'activeFilters' => $userFilter['active'],
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    /**
     * Liefert den Datensatz nur, wenn er zur erlaubten Datenmenge gehört.
     */
    public function findPermitted(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $scope = $this->getScopeConditions();
        $table = $this->guard->getConfig()->table;
        $where = array_merge(['`' . $table . '`.`id`=?'], $scope['columns']);

        $result = Database::getInstance()
            ->prepare('SELECT * FROM `' . $table . '` WHERE ' . implode(' AND ', array_map(static fn ($c) => '(' . $c . ')', $where)))
            ->limit(1)
            ->execute($id, ...$scope['values']);

        return $result->numRows ? $result->row() : null;
    }

    /**
     * Wie findPermitted(), wirft aber. Fremde und nicht existierende Datensätze sind bewusst
     * nicht unterscheidbar (keine Aufzählung von IDs möglich).
     */
    public function requirePermitted(int $id): array
    {
        return $this->findPermitted($id) ?? throw new AccessDeniedException(AccessDeniedException::RECORD, (string)$id);
    }

    /**
     * Daten für das Bearbeitungsformular (Berechtigung "bearbeiten" + Datensatzberechtigung).
     */
    public function loadForEdit(int $id): array
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_EDIT);

        return $this->requirePermitted($id);
    }

    // ---------------------------------------------------------------
    // Schreiben
    // ---------------------------------------------------------------

    /**
     * @param array<string, mixed> $values nur freigegebene Felder (bereits im Datenbankformat)
     */
    public function create(array $values): int
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_CREATE);
        $this->guard->assertFieldsEditable($values);

        $id = $this->insert($values);

        $this->dispatch(FrontendManagerEvents::RECORD_CREATED, fn () => new RecordCreatedEvent(
            ...$this->eventContext($id),
            record: $this->fetchRow($id),
        ));

        return $id;
    }

    /**
     * @param array<string, mixed> $values nur freigegebene Felder (bereits im Datenbankformat)
     */
    public function update(int $id, array $values): void
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_EDIT);
        $this->guard->assertFieldsEditable($values);

        $before = $this->requirePermitted($id);

        if ([] === $values) {
            return;
        }

        $this->persister->update($this->guard->getConfig()->table, $id, $values);

        // Ein bearbeitbares Feld kann Teil des Filters sein: Der Datensatz darf die erlaubte Menge
        // durch die Änderung nicht verlassen (z. B. einem anderen Benutzer zugeordnet werden).
        if (null === ($after = $this->findPermitted($id))) {
            $this->restore($id, $before, array_keys($values));

            throw new AccessDeniedException(AccessDeniedException::RECORD, 'record would leave the permitted scope');
        }

        $this->dispatch(FrontendManagerEvents::RECORD_UPDATED, fn () => new RecordUpdatedEvent(
            ...$this->eventContext($id),
            oldRecord: $before,
            newRecord: $after,
            submittedFields: array_keys($values),
        ));
    }

    /**
     * Dupliziert einen erlaubten Datensatz. Übernommen werden die Werte der aktiven Palette ohne
     * System-, eindeutige und doNotCopy-Felder; Besitzer und interne Werte werden neu gesetzt.
     */
    public function copy(int $id): int
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_COPY);
        $source = $this->requirePermitted($id);

        $policy = $this->guard->getPolicy();
        $values = [];
        $toggle = $this->guard->getToggleField();

        // Nur Felder der für den Quelldatensatz aktiven Palette (wie im Backend-Formular)
        $paletteFields = PaletteResolver::resolve($GLOBALS['TL_DCA'][$this->guard->getConfig()->table] ?? [], $source)['fields'];

        foreach (array_intersect_key($source, array_flip($paletteFields)) as $field => $value) {
            $cfg = $policy->getFieldConfig((string)$field);
            $eval = $cfg['eval'] ?? [];

            if (\in_array($field, FieldPolicy::SYSTEM_FIELDS, true) || !isset($cfg['inputType']) || !empty($eval['doNotCopy']) || !empty($eval['unique'])) {
                continue;
            }

            if (\in_array($field, [$this->guard->getOwnerField(), ...array_keys($this->guard->getInternalValues())], true)) {
                continue;
            }

            $values[(string)$field] = $this->toInputValue((string)$field, $value);
        }

        // Kopien sind zunächst unsichtbar (wie doNotCopy beim Veröffentlichungsfeld im Backend)
        if (null !== $toggle) {
            $values[$toggle] = $this->guard->getConfig()->toggleReverse ? '1' : '';
        }

        // Die Erstellungsberechtigung wird über "copy" erteilt; der neue Datensatz muss danach
        // ebenfalls in der erlaubten Menge liegen (insert() prüft das).
        $newId = $this->insert($values, (int)($source['pid'] ?? 0) ?: null, true);

        $this->dispatch(FrontendManagerEvents::RECORD_CREATED, fn () => new RecordCreatedEvent(
            ...$this->eventContext($newId),
            record: $this->fetchRow($newId),
            sourceId: $id,
        ));

        return $newId;
    }

    public function delete(int $id): void
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_DELETE);
        $before = $this->requirePermitted($id);

        $this->persister->delete($this->guard->getConfig()->table, $id);

        $this->dispatch(FrontendManagerEvents::RECORD_DELETED, fn () => new RecordDeletedEvent(
            ...$this->eventContext($id),
            record: $before,
        ));
    }

    /**
     * Schaltet die Sichtbarkeit um und liefert den neuen Zustand (true = sichtbar).
     */
    public function toggle(int $id): bool
    {
        $this->guard->assertOperation(ManagerConfig::OPERATION_TOGGLE);
        $row = $this->requirePermitted($id);
        $field = (string)$this->guard->getToggleField();
        $reverse = $this->guard->getConfig()->toggleReverse;

        $isVisible = $reverse ? empty($row[$field]) : !empty($row[$field]);
        $newVisible = !$isVisible;
        $stored = ($reverse ? !$newVisible : $newVisible) ? '1' : '';

        $this->persister->update($this->guard->getConfig()->table, $id, [$field => $stored], [$field]);

        if (null === ($after = $this->findPermitted($id))) {
            $this->restore($id, $row, [$field]);

            throw new AccessDeniedException(AccessDeniedException::RECORD, 'record would leave the permitted scope');
        }

        $this->dispatch(FrontendManagerEvents::RECORD_VISIBILITY_CHANGED, fn () => new RecordVisibilityChangedEvent(
            ...$this->eventContext($id),
            visible: $newVisible,
            field: $field,
            oldRecord: $row,
            newRecord: $after,
        ));

        return $newVisible;
    }

    public function isVisible(array $row): ?bool
    {
        $field = $this->guard->getToggleField();

        if (null === $field) {
            return null;
        }

        return $this->guard->getConfig()->toggleReverse ? empty($row[$field]) : !empty($row[$field]);
    }

    // ---------------------------------------------------------------
    // Intern
    // ---------------------------------------------------------------

    /**
     * Legt einen Datensatz an: Benutzerwerte + interne Werte + Besitzer. Liegt der neue Datensatz
     * nicht in der erlaubten Menge (Fehlkonfiguration), wird er wieder entfernt.
     */
    private function insert(array $values, ?int $pid = null, bool $isCopy = false): int
    {
        $table = $this->guard->getConfig()->table;
        $extra = [];

        foreach ($this->guard->getInternalValues() as $field => $raw) {
            $value = $this->resolveInsertTags($raw);

            if ('pid' === $field) {
                $pid = (int)$value;
                continue;
            }

            $values[$field] = $value;
            $extra[] = $field;
        }

        if (null !== ($owner = $this->guard->getOwnerField())) {
            $values[$owner] = $this->guard->getMember()->id;
            $extra[] = $owner;
        }

        if (null !== ($toggle = $this->guard->getToggleField()) && \array_key_exists($toggle, $values)) {
            $extra[] = $toggle;
        }

        if ($isCopy) {
            // Beim Kopieren dürfen auch Felder außerhalb der Benutzerkonfiguration übernommen werden,
            // aber nur, wenn sie in der aktiven Palette liegen (prüft der DcaRecordManager).
            $values = array_filter($values, static fn ($v) => null !== $v);
        }

        $result = $this->persister->create($table, $values, $pid, null, array_values(array_unique($extra)));
        $id = (int)$result['id'];

        if (null === $this->findPermitted($id)) {
            Database::getInstance()->prepare('DELETE FROM `' . $table . '` WHERE id=?')->execute($id);

            throw new AccessDeniedException(AccessDeniedException::RECORD, 'new record would be outside the permitted scope');
        }

        return $id;
    }

    /**
     * Löst ein Event aus. Das Event-Objekt wird nur erzeugt, wenn ein Dispatcher vorhanden ist.
     * Fehler in Listenern werden protokolliert und nicht weitergereicht: Der Datensatz ist bereits
     * gespeichert, eine fehlgeschlagene Benachrichtigung soll die Aktion des Mitglieds nicht als
     * gescheitert erscheinen lassen.
     *
     * @param \Closure(): AbstractRecordEvent $factory
     */
    private function dispatch(string $eventName, \Closure $factory): void
    {
        if (null === $this->dispatcher) {
            return;
        }

        try {
            $this->dispatcher->dispatch($factory(), $eventName);
        } catch (\Throwable $e) {
            $this->logger?->error(sprintf('Catalog frontend manager: listener for "%s" failed: %s', $eventName, $e->getMessage()), ['exception' => $e]);
        }
    }

    /**
     * @return array{table: string, id: int, member: MemberContext, moduleId: int}
     */
    private function eventContext(int $id): array
    {
        return [
            'table' => $this->guard->getConfig()->table,
            'id' => $id,
            'member' => $this->guard->getMember(),
            'moduleId' => $this->moduleId,
        ];
    }

    private function fetchRow(int $id): array
    {
        $result = Database::getInstance()
            ->prepare('SELECT * FROM `' . $this->guard->getConfig()->table . '` WHERE id=?')
            ->limit(1)
            ->execute($id);

        return $result->numRows ? $result->row() : ['id' => $id];
    }

    /**
     * Setzt Felder nach einer unzulässigen Änderung direkt auf die alten Werte zurück.
     */
    private function restore(int $id, array $before, array $fields): void
    {
        $set = array_intersect_key($before, array_flip(array_filter($fields, fn ($f) => $this->columnExists((string)$f))));

        if ([] !== $set) {
            Database::getInstance()->prepare('UPDATE `' . $this->guard->getConfig()->table . '` %s WHERE id=?')->set($set)->execute($id);
        }
    }

    /**
     * Wandelt einen gespeicherten Wert in das Eingabeformat des DcaRecordManager.
     */
    private function toInputValue(string $field, mixed $value): mixed
    {
        $cfg = $this->guard->getPolicy()->getFieldConfig($field);

        if ('fileTree' === ($cfg['inputType'] ?? null)) {
            $items = StringUtil::deserialize($value, true);
            $uuids = array_values(array_filter(array_map(static fn ($v) => Validator::isBinaryUuid($v) ? StringUtil::binToUuid($v) : (Validator::isStringUuid($v) ? $v : null), $items)));

            return !empty($cfg['eval']['multiple']) ? $uuids : ($uuids[0] ?? null);
        }

        if (isset($cfg['eval']['csv']) && \is_string($value) && '' !== $value) {
            return explode($cfg['eval']['csv'], $value);
        }

        $deserialized = StringUtil::deserialize($value);

        return \is_array($deserialized) ? $deserialized : $value;
    }

    private function resolveInsertTags(string $value): string
    {
        if (null !== $this->replaceInsertTags) {
            return (string)($this->replaceInsertTags)($value);
        }

        return (string)\Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::replaceInsertTags($value, false);
    }

    private function createScope(): RecordScope
    {
        $config = $this->guard->getConfig();

        return new RecordScope(
            $config->table,
            $GLOBALS['TL_DCA'][$config->table] ?? [],
            $config,
            $this->guard->getOwnerField(),
            $this->guard->getMember(),
            $this->replaceInsertTags,
        );
    }

    private function columnExists(string $field): bool
    {
        return ManagerConfig::isIdentifier($field) && Database::getInstance()->fieldExists($field, $this->guard->getConfig()->table);
    }
}
