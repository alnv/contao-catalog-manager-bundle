<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class Database {

    public function createTableIfNotExist($strTable) {

        if (\Database::getInstance()->tableExists($strTable, true)) {
            return false;
        }

        $strFields =
            "`stop` varchar(16) NOT NULL default ''," .
            "`start` varchar(16) NOT NULL default ''," .
            "`published` char(1) NOT NULL default ''," .
            "`alias` varchar(255) NOT NULL default ''," .
            "`pid` int(10) unsigned NOT NULL default '0',".
            "`id` int(10) unsigned NOT NULL auto_increment," .
            "`tstamp` int(10) unsigned NOT NULL default '0'," .
            "`sorting` int(10) unsigned NOT NULL default '0'," .
            "PRIMARY KEY  (`id`), INDEX (`alias`,`pid`)";

        \Database::getInstance()->prepare(sprintf('CREATE TABLE IF NOT EXISTS `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=UTF8', $strTable, $strFields))->execute();

        return true;
    }

    public function createCustomFieldsIfNotExists($strTable) {

        if (!\Database::getInstance()->tableExists($strTable, true)) {
            return null;
        }

        if (is_array($GLOBALS['CM_CUSTOM_FIELDS']) && !empty($GLOBALS['CM_CUSTOM_FIELDS'])) {
            foreach ($GLOBALS['CM_CUSTOM_FIELDS'] as $strField => $arrField) {
                if (isset($arrField['table']) && $arrField['table'] !== $strTable) {
                    continue;
                }
                $this->createFieldIfNotExist($strField, $strTable, $arrField['sql']);
            }
        }
    }

    public function renameTable($strOldTable, $strNewTable) {

        if (\Database::getInstance()->tableExists($strNewTable, true)) {
            return false;
        }

        \Database::getInstance()->prepare(sprintf('RENAME TABLE %s TO %s', $strOldTable, $strNewTable))->execute();

        return true;
    }

    public function deleteTable($strTable) {

        if (!\Database::getInstance()->tableExists($strTable)) {
            return false;
        }

        \Database::getInstance()->prepare(sprintf('DROP TABLE %s;', $strTable))->execute();

        return true;
    }

    public function createFieldIfNotExist($strField, $strTable, $strSql) {

        if (\Database::getInstance()->fieldExists($strField, $strTable, true)) {
            return false;
        }

        \Database::getInstance()->prepare(sprintf('ALTER TABLE %s ADD `%s` %s', $strTable, $strField, $strSql))->execute();

        return true;
    }

    public function renameFieldname($strOldField, $strNewField, $strTable, $strSql) {

        if (\Database::getInstance()->fieldExists($strNewField, $strTable, true)) {

            return false;
        }

        if (!\Database::getInstance()->fieldExists($strOldField, $strTable, true)) {

            return $this->createFieldIfNotExist($strNewField, $strTable, $strSql);
        }

        \Database::getInstance()->prepare(sprintf( 'ALTER TABLE %s CHANGE `%s` `%s` %s', $strTable, $strOldField, $strNewField, $strSql))->execute();

        return true;
    }

    public function changeFieldType($strField, $strTable, $strSql) {

        if (!\Database::getInstance()->fieldExists($strField, $strTable, true)) {
            return null;
        }

        try {
            \Database::getInstance()->prepare(sprintf( 'ALTER TABLE %s MODIFY COLUMN %s %s', $strTable, $strField, $strSql))->execute();
        } catch (\Exception $exception) {
            //
        }

        return true;
    }
}