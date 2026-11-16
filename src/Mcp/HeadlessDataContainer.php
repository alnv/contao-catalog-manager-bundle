<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Contao\Database;
use Contao\DataContainer;

/**
 * Ein DataContainer ohne Backend-Oberfläche.
 *
 * Damit lassen sich die save_/onsubmit_/ondelete_/options_callbacks des
 * Catalog-Managers genauso aufrufen, wie es DC_Table beim Speichern im
 * Backend tut. Nur so entstehen z. B. die Datenbanktabelle eines neuen
 * Katalogs und die Spalten neuer Felder.
 */
final class HeadlessDataContainer extends DataContainer
{
    private string $palette = '';

    public function __construct(string $table, int|string|null $id, RecordStub $record)
    {
        parent::__construct();

        $this->strTable = $table;
        $this->intId = $id;
        $this->objActiveRecord = $record;
    }

    public function setField(?string $field, mixed $currentValue = null): void
    {
        $this->strField = $field;
        $this->varValue = $currentValue;
    }

    public function setPalette(string $palette): void
    {
        $this->palette = $palette;
    }

    public function getRecord(): RecordStub
    {
        return $this->objActiveRecord;
    }

    public function getPalette()
    {
        return $this->palette;
    }

    protected function save($varValue)
    {
        // Gespeichert wird zentral im DcaRecordManager.
    }

    /**
     * Ohne Backend-Benutzer würden die Contao-Voter den Zugriff verweigern,
     * deshalb wird der Datensatz hier direkt geliefert.
     */
    public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
    {
        $id = $id ?: $this->intId;
        $table = $table ?: $this->strTable;

        if ($id == $this->intId && $table === $this->strTable) {
            return $this->objActiveRecord->row();
        }

        if (!$id || !preg_match('/^[a-z0-9_]+$/i', (string) $table)) {
            return null;
        }

        $result = Database::getInstance()->prepare('SELECT * FROM ' . $table . ' WHERE id=?')->limit(1)->execute($id);

        return $result->numRows ? $result->row() : null;
    }

    public function getActiveRecord(): array|null
    {
        return $this->objActiveRecord->row();
    }
}
