<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class Application {

    public function initializeBackendModules() {

        $objCatalogCollection = new CatalogCollection();
        $arrCatalogs = $objCatalogCollection->getCatalogs( 'catalog' );

        foreach ( $arrCatalogs as $arrCatalog ) {

            if ( !$arrCatalog['navigation'] ) {

                continue;
            }

            $arrModule = [];
            $arrModule[ $arrCatalog['navigation'] ] = [];
            $arrModule[ $arrCatalog['navigation'] ][ $arrCatalog['module'] ] = $this->generateBeModConfig( $arrCatalog );

            array_insert( $GLOBALS['BE_MOD'], (int) $arrCatalog['position'], $arrModule );
        }
    }


    public function generateBeModConfig( $arrCatalog ) {

        $arrTables = [ $arrCatalog['table'] ];

        if ( is_array( $arrCatalog['children'] ) && !empty( $arrCatalog['children'] ) ) {

            foreach ( $arrCatalog['children'] as $strTable ) {

                $arrTables[] = $strTable;
            }
        }

        return [

            'name' => '',
            'tables' => $arrTables
        ];
    }


    public function initializeDataContainerArrays() {

        $strModule = \Input::get('do');

        if ( !$strModule ) {

            return null;
        }

        $this->initializeDataContainerArrayByTable( $strModule );
    }



    protected function initializeDataContainerArrayByTable( $strTable ) {

        $objVDataContainerArray = new VirtualDataContainerArray( $strTable );
        $objVDataContainerArray->generate();

        $arrRelatedTables = $objVDataContainerArray->getRelatedTables();

        if ( is_array( $arrRelatedTables ) && !empty( $arrRelatedTables ) ) {

            foreach ( $arrRelatedTables as $strTable ) {

                $this->initializeDataContainerArrayByTable( $strTable );
            }
        }
    }
}