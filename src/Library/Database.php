<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogTableGuard;
use Contao\Database as ContaoDatabase;
use Contao\Message;

class Database
{

    public function createTableIfNotExist($strTable): void
    {
        $strQuoted = $this->quoteTable($strTable);

        if (ContaoDatabase::getInstance()->tableExists($strTable, true)) {
            return;
        }

        $strFields =
            "`stop` varchar(16) NOT NULL default ''," .
            "`start` varchar(16) NOT NULL default ''," .
            "`published` char(1) NOT NULL default ''," .
            "`alias` varchar(255) NOT NULL default ''," .
            "`pid` int(10) unsigned NOT NULL default '0'," .
            "`id` int(10) unsigned NOT NULL auto_increment," .
            "`tstamp` int(10) unsigned NOT NULL default '0'," .
            "`sorting` int(10) unsigned NOT NULL default '0'," .
            "PRIMARY KEY  (`id`), INDEX (`alias`,`pid`)";

        ContaoDatabase::getInstance()->prepare(sprintf('CREATE TABLE IF NOT EXISTS %s (%s) ENGINE=InnoDB DEFAULT CHARSET=UTF8', $strQuoted, $strFields))->execute();
    }

    public function createCustomFieldsIfNotExists($strTable)
    {
        if (!CatalogTableGuard::isValidIdentifier($strTable) || !ContaoDatabase::getInstance()->tableExists($strTable, true)) {
            return null;
        }

        if (is_array($GLOBALS['CM_CUSTOM_FIELDS'] ?? null) && !empty($GLOBALS['CM_CUSTOM_FIELDS'])) {
            foreach ($GLOBALS['CM_CUSTOM_FIELDS'] as $strField => $arrField) {
                if (isset($arrField['table']) && $arrField['table'] !== $strTable) {
                    continue;
                }
                $this->createFieldIfNotExist($strField, $strTable, $arrField['sql']);
            }
        }

        return true;
    }

    /**
     * @param int|null $intCatalogId ID des Katalogs, der die Tabelle umbenennt (für die Prüfung auf Mehrfachnutzung)
     */
    public function renameTable($strOldTable, $strNewTable, ?int $intCatalogId = null)
    {
        $strOldQuoted = $this->quoteTable($strOldTable);
        $strNewQuoted = $this->quoteTable($strNewTable);

        if (ContaoDatabase::getInstance()->tableExists($strNewTable, true)) {
            return false;
        }

        if (CatalogTableGuard::isProtectedTable($strOldTable, $intCatalogId)) {
            throw new \RuntimeException(sprintf('Table "%s" does not belong exclusively to the catalog manager and cannot be renamed.', $strOldTable));
        }

        ContaoDatabase::getInstance()->prepare(sprintf('RENAME TABLE %s TO %s', $strOldQuoted, $strNewQuoted))->execute();

        return true;
    }

    /**
     * @param int|null $intCatalogId ID des Katalogs, der gelöscht wird (für die Prüfung auf Mehrfachnutzung)
     */
    public function deleteTable($strTable, ?int $intCatalogId = null)
    {
        $strQuoted = $this->quoteTable($strTable);

        if (!ContaoDatabase::getInstance()->tableExists($strTable, true)) {
            return false;
        }

        if (CatalogTableGuard::isProtectedTable($strTable, $intCatalogId)) {
            return false;
        }

        ContaoDatabase::getInstance()->prepare(sprintf('DROP TABLE %s', $strQuoted))->execute();

        return true;
    }

    public function createFieldIfNotExist($strField, $strTable, $strSql)
    {
        $strTableQuoted = $this->quoteTable($strTable);
        $strFieldQuoted = $this->quoteField($strField);

        if (ContaoDatabase::getInstance()->fieldExists($strField, $strTable, true)) {
            return false;
        }

        ContaoDatabase::getInstance()->prepare(sprintf('ALTER TABLE %s ADD %s %s', $strTableQuoted, $strFieldQuoted, $strSql))->execute();

        return true;
    }

    public function renameFieldname($strOldField, $strNewField, $strTable, $strSql)
    {
        $strTableQuoted = $this->quoteTable($strTable);
        $strOldQuoted = $this->quoteField($strOldField);
        $strNewQuoted = $this->quoteField($strNewField);

        if (ContaoDatabase::getInstance()->fieldExists($strNewField, $strTable, true)) {
            return false;
        }

        if (!ContaoDatabase::getInstance()->fieldExists($strOldField, $strTable, true)) {
            return $this->createFieldIfNotExist($strNewField, $strTable, $strSql);
        }

        ContaoDatabase::getInstance()->prepare(sprintf('ALTER TABLE %s CHANGE %s %s %s', $strTableQuoted, $strOldQuoted, $strNewQuoted, $strSql))->execute();

        return true;
    }

    public function changeFieldType($strField, $strTable, $strSql)
    {
        $strTableQuoted = $this->quoteTable($strTable);
        $strFieldQuoted = $this->quoteField($strField);

        if (!ContaoDatabase::getInstance()->fieldExists($strField, $strTable, true)) {
            return null;
        }

        try {
            ContaoDatabase::getInstance()->prepare(sprintf('ALTER TABLE %s MODIFY COLUMN %s %s', $strTableQuoted, $strFieldQuoted, $strSql))->execute();
        } catch (\Exception $exception) {
            Message::addError($exception->getMessage());
        }

        return true;
    }

    private function quoteTable($strTable): string
    {
        CatalogTableGuard::assertValidIdentifier($strTable, 'table name');

        return '`' . $strTable . '`';
    }

    private function quoteField($strField): string
    {
        CatalogTableGuard::assertValidIdentifier($strField, 'field name');

        return '`' . $strField . '`';
    }
}
