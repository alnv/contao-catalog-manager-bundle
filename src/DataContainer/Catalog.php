<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;


class Catalog {


    public function getCatalogTypes() {

        return array_keys( $GLOBALS['TL_LANG']['tl_catalog']['reference']['type'] );
    }


    public function getSortingTypes() {

        return array_keys( $GLOBALS['TL_LANG']['tl_catalog']['reference']['sortingType'] );
    }


    public function getDataContainers() {

        return $GLOBALS['CM_DATA_CONTAINERS'];
    }


    public function getModes( \DataContainer $objDataContainer ) {

        $arrModes = array_keys( $GLOBALS['TL_LANG']['tl_catalog']['reference']['mode'] );

        if ( !$objDataContainer->activeRecord->pid ) {

            if ( ( $intPos = array_search( 'parent', $arrModes ) ) !== false ) {

                unset( $arrModes[ $intPos ] );
            }
        }

        else {

            if ( ( $intPos = array_search( 'tree', $arrModes ) ) !== false ) {

                unset( $arrModes[ $intPos ] );
            }
        }

        return array_values( $arrModes );
    }


    public function getFlags() {

        return [ '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ];
    }


    public function getParentFields( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->pid ) {

            return [];
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( $objDataContainer->activeRecord->pid );

        return $objCatalog->getNaturalFields();
    }


    public function getFields( $objDataContainer = null ) {

        if ( $objDataContainer === null ) {

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

        if ( $strTable == $objDataContainer->activeRecord->table && $objDatabase->tableExists( $strTable, true ) ) {

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


    public function createCustomFields( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->createCustomFieldsIfNotExists( $objDataContainer->activeRecord->table );
    }


    public function deleteTable( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->deleteTable( $objDataContainer->activeRecord->table );
    }


    public function getOrderByStatements() {

        return [

            'ASC',
            'DESC'
        ];
    }
}