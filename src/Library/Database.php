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

            return true;
        }

        return false;
    }


    public function renameTable( $strOldTable, $strNewTable ) {

        return true;
    }
}