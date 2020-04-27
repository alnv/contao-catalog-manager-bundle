<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class Application {

    public function initializeBackendModules() {

        $objCatalogCollection = new CatalogCollection();
        $arrCatalogs = $objCatalogCollection->getCatalogs('catalog');

        foreach ( $arrCatalogs as $arrCatalog ) {

            if ( !$arrCatalog['navigation'] ) {

                continue;
            }

            $arrModule = [];
            $arrModule[ $arrCatalog['module'] ] = $this->generateBeModConfig( $arrCatalog );
            array_insert( $GLOBALS['BE_MOD'][ $arrCatalog['navigation'] ], (int) $arrCatalog['position'], $arrModule );
        }
    }

    public function generateBeModConfig( $arrCatalog ) {

        $arrTables = [ $arrCatalog['table'] ];

        if ( is_array( $arrCatalog['related'] ) && !empty( $arrCatalog['related'] ) ) {
            foreach ( $arrCatalog['related'] as $strTable ) {
                $arrTables[] = $strTable;
            }
        }

        if ( !isset( $GLOBALS['TL_LANG']['MOD'][ $arrCatalog['module'] ] ) ) {
            $GLOBALS['TL_LANG']['MOD'][ $arrCatalog['module'] ] = [
                \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate($arrCatalog['module'], $arrCatalog['name']),
                \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate($arrCatalog['module'] . '.' . 'description', $arrCatalog['description']),
            ];
        }

        return [

            'name' => $arrCatalog['module'],
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

    public function initializeDataContainerArrayByTable( $strTable ) {

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