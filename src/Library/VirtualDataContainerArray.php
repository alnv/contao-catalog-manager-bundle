<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;


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
            'labels' => [
                'fields' => []
            ],
            'sorting' => [
                'mode' => 0
            ]
        ];

        if ( $this->arrCatalog['enablePanel'] ) {

            $arrList['sorting']['panelLayout'] = 'filter,search,sort;limit';
        }

        if ( $this->arrCatalog['showColumns'] ) {

            $arrList['labels']['showColumns'] = true;
        }

        if ( !empty( $this->arrCatalog['columns'] ) ) {

            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
        }

        if ( $this->arrCatalog['sortingType'] ) {

            if ( $this->arrCatalog['sortingType'] == 'fixed' ) {

                $arrList['sorting']['mode'] = 1;
                $arrList['sorting']['flag'] = (int) $this->arrCatalog['flag'];
                $arrList['sorting']['fields'] = [ $this->arrCatalog['flagField'] ];

                if ( empty( $arrList['labels']['fields'] ) ) {

                    $arrList['labels']['fields'] = [ $this->arrCatalog['flagField'] ];
                }
            }

            if ( $this->arrCatalog['sortingType'] == 'switchable' ) {

                $arrSortingFields = [];
                $arrList['sorting']['mode'] = 2;
                $arrList['sorting']['fields'] = [];

                foreach ( $this->arrCatalog['order'] as $arrOrder ) {

                    if ( isset( $arrOrder['field'] ) && $arrOrder['field'] ) {

                        $arrList['sorting']['fields'][] = $arrOrder['field'] . ( $arrOrder['order'] ? ' ' . $arrOrder['order'] : ' ASC' );
                        $arrSortingFields[] = $arrOrder['field'];
                    }
                }

                if ( empty( $arrList['labels']['fields'] ) ) {

                    $arrList['labels']['fields'] = $arrSortingFields;
                }
            }
        }

        if ( count( $arrList['labels']['fields'] ) > 1 && !$arrList['labels']['showColumns'] ) {

            $arrList['labels']['label_callback'] = function ( $arrRow, $strLabel, \DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false  ) use ( $arrList ) {

                return Toolkit::renderRow( $arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields );
            };
        }

        if ( $this->arrCatalog['mode'] == 'parent' ) {

            $arrList['sorting']['mode'] = 4;
            $arrList['sorting']['headerFields'] = [ 'name' ];
            $arrList['sorting']['child_record_callback'] =  function ( $arrRow ) use ( $arrList ) {

                return Toolkit::renderRow( $arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields );
            };

            $arrList['labels']['showColumns'] = false;
            $arrList['labels']['fields'] = [];
        }

        if ( $this->arrCatalog['mode'] == 'tree' ) {

            $arrList['sorting']['mode'] = 5;
            $arrList['sorting']['icon'] = 'articles.svg';
            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
            $arrList['labels']['label_callback'] =  function ( $arrRow, $strLabel, \DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false  ) use ( $arrList ) {

                return Toolkit::renderTreeRow( $arrRow, $strLabel, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields );
            };

            $arrList['sorting']['fields'] = [];
            $arrList['labels']['showColumns'] = false;
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
            'config' => [
                'dataContainer' => 'Table',
                'onsubmit_callback' => [
                    function( \DataContainer $objDataContainer ) {

                        Toolkit::saveGeoCoordinates( $this->arrCatalog['table'], $objDataContainer->activeRecord->row() );
                    }
                ]
            ],
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