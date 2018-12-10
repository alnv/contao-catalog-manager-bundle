<?php

namespace Alnv\CatalogManagerBundle\Library;


class Database {


    protected $objDatabase = null;


    public function __construct() {

        if ( $this->objDatabase === null ) {

            $this->objDatabase = \Database::getInstance();
        }
    }


    public function createTableIfNotExist( $strTable ) {

        if ( $this->objDatabase->tableExists( $strTable ) ) {

            return false;
        }

        $strFields =
            "`stop` varchar(16) NOT NULL default ''," .
            "`start` varchar(16) NOT NULL default ''," .
            "`invisible` char(1) NOT NULL default ''," .
            "`alias` varchar(255) NOT NULL default ''," .
            "`pid` int(10) unsigned NOT NULL default '0',".
            "`id` int(10) unsigned NOT NULL auto_increment," .
            "`tstamp` int(10) unsigned NOT NULL default '0'," .
            "`sorting` int(10) unsigned NOT NULL default '0'," .
            "PRIMARY KEY  (`id`), INDEX (`alias`,`pid`)";

        $this->objDatabase->prepare( sprintf( 'CREATE TABLE IF NOT EXISTS `%s` ( %s ) ENGINE=MyISAM DEFAULT CHARSET=UTF8', $strTable, $strFields ) )->execute();

        return true;
    }


    public function renameTable( $strOldTable, $strNewTable ) {

        if ( $this->objDatabase->tableExists( $strNewTable ) ) {

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
}