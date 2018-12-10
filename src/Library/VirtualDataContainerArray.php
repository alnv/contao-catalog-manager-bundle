<?php

namespace Alnv\CatalogManagerBundle\Library;


class VirtualDataContainerArray {


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

        $objCatalog = new Catalog( $strTable );
        $arrCatalog = $objCatalog->getCatalog();

        if ( empty( $arrCatalog ) ) {

            return null;
        }

        $GLOBALS['TL_DCA'][ $arrCatalog['table'] ] = [

            'config' => [

                'dataContainer' => 'Table'
            ],

            'fields' => [

                //
            ]
        ];

        if ( is_array( $arrCatalog['children'] ) && !empty( $arrCatalog['children'] ) ) {

            foreach ( $arrCatalog['children'] as $strTable ) {

                $this->initializeDataContainerArrayByTable( $strTable );
            }
        }
    }
}