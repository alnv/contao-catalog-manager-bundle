<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Contao\Database;

/**
 * Die Werkzeuge, die der MCP-Server der KI anbietet.
 *
 * Jede öffentliche Methode wird in CatalogMcpCommand als Tool registriert.
 */
final class Tools
{
    public function __construct(
        private readonly DcaRecordManager $records,
        private readonly TemplateManager $templates,
        private readonly bool $readOnly = false,
    ) {
    }

    // --- Übersicht & Konfigurationsmöglichkeiten ---------------------------

    public function catalogOverview(): array
    {
        return $this->records->overview();
    }

    public function configDescribe(string $table, array $context = [], ?int $id = null): array
    {
        return $this->records->describe($table, $context, $id);
    }

    // --- CRUD ----------------------------------------------------------------

    public function recordList(string $table, array $where = [], ?string $orderBy = null, int $limit = 50, int $offset = 0, array $columns = []): array
    {
        return $this->records->list($table, $where, $orderBy, $limit, $offset, $columns);
    }

    public function recordGet(string $table, int $id): array
    {
        return $this->records->get($table, $id);
    }

    public function recordCreate(string $table, array $values, ?int $pid = null, ?string $ptable = null): array
    {
        $this->assertWritable();

        return $this->records->create($table, $values, $pid, $ptable);
    }

    public function recordUpdate(string $table, int $id, array $values): array
    {
        $this->assertWritable();

        return $this->records->update($table, $id, $values);
    }

    public function recordDelete(string $table, int $id, bool $confirm = false): array
    {
        $this->assertWritable();

        if (!$confirm) {
            return [
                'deleted' => false,
                'preview' => $this->records->get($table, $id),
                'hint' => 'Noch nichts gelöscht. Zum Löschen erneut mit confirm=true aufrufen. Bei tl_catalog wird auch die Datentabelle mit allen Einträgen entfernt.',
            ];
        }

        return $this->records->delete($table, $id);
    }

    // --- Templates -----------------------------------------------------------

    public function templateList(): array
    {
        return $this->templates->list();
    }

    public function templateRead(string $name, string $folder = ''): array
    {
        return $this->templates->read($name, $folder);
    }

    public function templateContext(string $table): array
    {
        $catalog = Database::getInstance()->prepare('SELECT * FROM tl_catalog WHERE `table`=?')->limit(1)->execute($table);

        if (!$catalog->numRows) {
            throw new \InvalidArgumentException(sprintf('Kein Katalog mit der Tabelle "%s".', $table));
        }

        $fields = Database::getInstance()
            ->prepare('SELECT name, fieldname, type, role, multiple, published FROM tl_catalog_field WHERE pid=? ORDER BY sorting')
            ->execute($catalog->id)
            ->fetchAllAssoc();

        $roles = array_values(array_filter(array_map(static fn ($f) => $f['role'] ?? '', $fields)));

        return [
            'catalog' => ['id' => (int) $catalog->id, 'name' => $catalog->name, 'table' => $table],
            'fields' => $fields,
            'roles' => $roles,
            'templateVariables' => [
                '$this->FELDNAME' => 'aufbereiteter Ausgabewert eines Feldes (z. B. $this->title)',
                '$this->origin[\'FELDNAME\']' => 'Rohwert aus der Datenbank',
                '$this->id, $this->alias, $this->pid' => 'Systemfelder des Eintrags',
                '$this->masterUrl' => 'Link zur Detailseite (leer, wenn keine Detailseite gesetzt ist)',
                '$this->roleResolver()->getValueByRole(\'title\')' => 'Wert über die Feldrolle (title, teaser, image, content, …)',
                '$this->parseImage($wert)' => 'Bildpfad aus einem Upload-Feld',
                '$this->parseArray($wert, \', \', \'label\')' => 'Mehrfachwerte als Text',
                '$this->getRelated(\'FELDNAME\')' => 'verknüpfte Datensätze eines Relationsfeldes',
                '$this->getParent()' => 'Elterneintrag',
                '$this->getContentElements()' => 'Inhaltselemente des Eintrags (nur Detailansicht)',
                '$this->shareButtons()' => 'Teilen-Buttons',
                '$this->iCalendarUrl()' => 'iCal-Link (bei Terminen)',
            ],
            'templatePrefixes' => TemplateManager::PREFIXES,
            'tips' => [
                'Als Ausgangspunkt template_read mit "cm_listing_default" oder "cm_master_default" verwenden.',
                'Ausgaben, die nicht schon aufbereitet sind, mit htmlspecialchars() oder StringUtil::specialchars() absichern.',
                'Das neue Template danach per record_update im Modul (tl_module.cmTemplate bzw. customTpl) auswählen.',
            ],
        ];
    }

    public function templateWrite(string $name, string $content, string $folder = '', bool $overwrite = false): array
    {
        $this->assertWritable();

        return $this->templates->write($name, $content, $folder, $overwrite);
    }

    public function templateDelete(string $name, string $folder = '', bool $confirm = false): array
    {
        $this->assertWritable();

        if (!$confirm) {
            return ['deleted' => false, 'hint' => 'Noch nichts gelöscht. Zum Löschen erneut mit confirm=true aufrufen (es wird eine Sicherung angelegt).'];
        }

        return $this->templates->delete($name, $folder);
    }

    private function assertWritable(): void
    {
        if ($this->readOnly) {
            throw new \RuntimeException('Der Server läuft im Nur-Lese-Modus (--read-only).');
        }
    }
}
