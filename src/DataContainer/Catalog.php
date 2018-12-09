<?php

namespace Alnv\CatalogManagerBundle\DataContainer;


class Catalog {


    public function getCatalogTypes() {

        return [ 'catalog', 'subitem', 'core' ];
    }


    public function getViews() {

        return [ '0', '1', '2', '5' ];
    }


    public function getFlags() {

        return [ '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ];
    }


    public function getParentFields() {

        return [];
    }


    public function getFields() {

        return [];
    }


    public function getNavigation() {

        return [];
    }


    public function watchTable( $strTable, \DataContainer $objDataContainer ) {

        if ( !$strTable ) {

            return '';
        }

        $objDatabase = new \Alnv\CatalogManagerBundle\Library\Database();

        if ( !$objDataContainer->activeRecord->table ) {

            $objDatabase->createTableIfNotExist( $strTable );
        }

        if ( $strTable != $objDataContainer->activeRecord->table ) {

            $objDatabase->renameTable( $objDataContainer->activeRecord->table, $strTable );
        }

        exit;
    }
}