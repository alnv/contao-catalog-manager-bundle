<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;


class Catalog {


    public function getCatalogTypes() {

        return [ 'catalog', 'core' ];
    }


    public function getFieldTypes() {

        return [ 'text', 'textarea', 'select', 'radio', 'checkbox', 'upload' ];
    }


    public function getRoles() {

        return [];
    }


    public function getModes() {

        return [ 'none', 'flex', 'fixed', 'custom', 'tree' ];
    }


    public function getFlags() {

        return [ '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ];
    }


    public function getParentFields( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->parent ) {

            return [];
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( $objDataContainer->activeRecord->parent );

        return $objCatalog->getNaturalFields();
    }


    public function getFields( \DataContainer $objDataContainer = null ) {

        if ( $objDataContainer == null ) {

            return [];
        }

        if ( !$objDataContainer->activeRecord->table ) {

            return [];
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( $objDataContainer->activeRecord->table );

        return $objCatalog->getNaturalFields();
    }


    public function generateModulename( \DataContainer $objDataContainer ) {

        if ( $objDataContainer->activeRecord->type !== 'catalog' || !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabase = \Database::getInstance();
        $strModulename = 'module_' . strtolower( $objDataContainer->activeRecord->table );
        $objDatabase->prepare('UPDATE ' . $objDataContainer->table . ' %s WHERE id = ?')->set([ 'tstamp' => time(), 'module' => $strModulename ])->execute( $objDataContainer->id );
    }


    public function getNavigation() {

        $arrReturn = [];

        if ( !is_array( $GLOBALS['BE_MOD'] ) || empty( $GLOBALS['BE_MOD'] ) ) {

            return $arrReturn;
        }

        foreach ( $GLOBALS['BE_MOD'] as $strModulename => $arrModules ) {

            $strModuleLabel = $GLOBALS['TL_LANG']['MOD'][ $strModulename ] ?: $strModulename;

            $arrReturn[ $strModulename ] = $strModuleLabel;
        }

        return $arrReturn;
    }


    public function watchTable( $strTable, \DataContainer $objDataContainer ) {

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabase = \Database::getInstance();

        if ( !$strTable ) {

            return '';
        }

        if ( $strTable == $objDataContainer->activeRecord->table && $objDatabase->tableExists( $strTable ) ) {

            return $strTable;
        }

        if ( $strTable != $objDataContainer->activeRecord->table && $objDataContainer->activeRecord->table ) {

            if ( !$objDatabaseBuilder->renameTable( $objDataContainer->activeRecord->table, $strTable ) ) {

                throw new \Exception( sprintf( 'table "%s" already exists in catalog manager.', $strTable ) );
            }
        }

        if ( !$objDatabaseBuilder->createTableIfNotExist( $strTable ) ) {

            throw new \Exception( sprintf( 'table "%s" already exists in catalog manager.', $strTable ) );
        }

        return $strTable;
    }


    public function deleteTable( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->deleteTable( $objDataContainer->activeRecord->table );
    }


    public function getOrderByStatements( $strValue ) {

        return [

            'asc',
            'desc'
        ];
    }
}