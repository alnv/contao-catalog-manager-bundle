<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class Database {

    protected $objDatabase = null;

    public function __construct() {

        if ( $this->objDatabase === null ) {
            $this->objDatabase = \Database::getInstance();
        }
    }

    public function createTableIfNotExist( $strTable ) {

        if ( $this->objDatabase->tableExists( $strTable, true ) ) {
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

        $this->objDatabase->prepare( sprintf( 'CREATE TABLE IF NOT EXISTS `%s` ( %s ) ENGINE=MyISAM DEFAULT CHARSET=UTF8', $strTable, $strFields ) )->execute();

        return true;
    }

    public function createCustomFieldsIfNotExists( $strTable ) {

        if ( !$this->objDatabase->tableExists( $strTable, true ) ) {
            return null;
        }

        if ( is_array( $GLOBALS['CM_CUSTOM_FIELDS'] ) && !empty( $GLOBALS['CM_CUSTOM_FIELDS'] ) ) {
            foreach ( $GLOBALS['CM_CUSTOM_FIELDS'] as $strField => $arrField ) {
                if ( isset( $arrField['table'] ) && $arrField['table'] !== $strTable ) {
                    continue;
                }
                $this->createFieldIfNotExist($strField, $strTable, $arrField['sql']);
            }
        }
    }

    public function renameTable( $strOldTable, $strNewTable ) {

        if ( $this->objDatabase->tableExists( $strNewTable, true ) ) {
            return false;
        }

        $this->objDatabase->prepare( sprintf( 'RENAME TABLE %s TO %s', $strOldTable, $strNewTable ) )->execute();

        return true;
    }

    public function deleteTable( $strTable ) {

        if ( !$this->objDatabase->tableExists( $strTable ) ) {
            return false;
        }

        $this->objDatabase->prepare( sprintf( 'DROP TABLE %s;', $strTable ) )->execute();

        return true;
    }

    public function createFieldIfNotExist( $strField, $strTable, $strSql ) {

        if ( $this->objDatabase->fieldExists( $strField, $strTable, true ) ) {
            return false;
        }

        $this->objDatabase->prepare( sprintf( 'ALTER TABLE %s ADD `%s` %s', $strTable, $strField, $strSql ) )->execute();

        return true;
    }

    public function renameFieldname( $strOldField, $strNewField, $strTable, $strSql ) {

        if ( $this->objDatabase->fieldExists( $strNewField, $strTable, true ) ) {

            return false;
        }

        if ( !$this->objDatabase->fieldExists( $strOldField, $strTable, true ) ) {

            return $this->createFieldIfNotExist($strNewField, $strTable, $strSql);
        }

        $this->objDatabase->prepare( sprintf( 'ALTER TABLE %s CHANGE `%s` `%s` %s', $strTable, $strOldField, $strNewField, $strSql ) )->execute();

        return true;
    }

    public function changeFieldType( $strField, $strTable, $strSql ) {

        if ( !$this->objDatabase->fieldExists( $strField, $strTable, true ) ) {

            return null;
        }

        //todo try and catch block
        $this->objDatabase->prepare( sprintf( 'ALTER TABLE %s MODIFY COLUMN %s %s', $strTable, $strField, $strSql ) )->execute();

        return true;
    }
}