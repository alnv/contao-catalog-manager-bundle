<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class VirtualDataContainerArray {


    protected $arrCatalog = [];
    protected $arrFields = [];


    public function __construct( $strModule ) {

        $objCatalog = new Catalog( $strModule );
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();
        $this->generateEmptyDataContainer();
    }


    protected function setConfig() {

        if (  $this->arrCatalog['ptable'] ) {

            $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['ptable'] = $this->arrCatalog['ptable'];
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['ctable'] = $this->arrCatalog['ctable'];
    }


    protected function setList() {

        $arrList = [

            'label' => [],
            'sorting' => []
        ];

        if ( $this->arrCatalog['showColumns'] ) {

            $arrList['labels']['showColumns'] = true;
            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
        }

        switch ( $this->arrCatalog['mode'] ) {

            case 'none':

                $arrList['sorting']['mode'] = 0;

                break;

            case 'flex':

                $arrList['sorting']['mode'] = 2;
                $arrList['sorting']['flag'] = $this->arrCatalog['flag'];
                $arrList['sorting']['fields'] = [ 'id' ];

                break;

            case 'fixed':

                $arrList['sorting']['mode'] = 1;

                break;

            case 'custom':

                break;

            case 'tree':

                break;
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['label'] = $arrList['labels'];
        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['sorting'] = $arrList['sorting'];
    }


    protected function setFields() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['fields'] = $this->arrFields;
    }


    protected function setPalettes() {

        // @todo implement palettes builder with extra table for configs…
        $arrPalette = [];

        foreach ( $this->arrFields as $strFieldname => $arrField ) {

            $arrPalette[] =  $strFieldname;
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['palettes']['default'] = implode(',', $arrPalette );
    }


    protected function setSubPalettes() {

        //
    }


    protected function setLabels() {

        foreach ( $this->arrFields as $strFieldname => $arrField ) {

            if ( isset( $GLOBALS['TL_LANG'][ $this->arrCatalog['table'] ][ $strFieldname ] ) ) {

                continue;
            }

            $GLOBALS['TL_LANG'][ $this->arrCatalog['table'] ][ $strFieldname ] = [
                \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate( $this->arrCatalog['table'] . '.' . $strFieldname, $arrField['name'] ),
                '' // @todo description
            ];
        }
    }


    protected function generateEmptyDataContainer() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ] = [
            'config' => [ 'dataContainer' => 'Table' ],
            'list' => [
                'label' => [],
                'sorting' => [],
                'operations' => [
                    'edit' => [
                        'href' => 'act=edit',
                        'icon' => 'header.gif'
                    ],
                    'copy' => [
                        'href' => 'act=copy',
                        'icon' => 'copy.gif'
                    ],
                    'delete' => [
                        'href' => 'act=delete',
                        'icon' => 'delete.gif',
                        'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
                    ],
                    'show' => [
                        'href' => 'act=show',
                        'icon' => 'show.gif'
                    ]
                ],
                'global_operations' => [
                    'all' => [
                        'href' => 'act=select',
                        'class' => 'header_edit_all',
                        'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
                    ]
                ]
            ],
            'palettes' => [ '__selector__' => [], 'default' => '' ],
            'subpalettes' => [],
            'fields' => []
        ];
    }


    public function getRelatedTables() {

        return $this->arrCatalog['related'];
    }


    protected function setOperations() {

        if ( empty( $this->arrCatalog['ctable'] ) || !is_array( $this->arrCatalog['ctable'] ) ) {

            return null;
        }

        foreach ( $this->arrCatalog['ctable'] as $strTable ) {

            $arrOperation = [];
            $arrOperation[ 'child_' . $strTable ] = [
                'href' => 'table=' . $strTable,
                'icon' => 'edit.gif'
            ];

            array_insert( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'], 1, $arrOperation );
        }
    }


    public function generate() {

        $this->setConfig();
        $this->setList();
        $this->setOperations();
        $this->setPalettes();
        $this->setSubPalettes();
        $this->setFields();
        $this->setLabels();
    }
}