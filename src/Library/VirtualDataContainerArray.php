<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;

class VirtualDataContainerArray extends \System {

    protected $arrCatalog = [];
    protected $arrFields = [];

    public function __construct( $strModule ) {

        $objCatalog = new Catalog( $strModule );
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();
        $this->generateEmptyDataContainer();
    }

    protected function setConfig() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['_table'] = $this->arrCatalog['table'];
        if (  $this->arrCatalog['ptable'] ) {
            $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['ptable'] = $this->arrCatalog['ptable'];
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['ctable'] = $this->arrCatalog['ctable'];
        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['dataContainer'] = $this->arrCatalog['dataContainer'];

        if ( $this->arrCatalog['enableGeocoding'] ) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['onsubmit_callback'][] = function( \DataContainer $objDataContainer ) {
                if ($objDataContainer->activeRecord) {
                    Toolkit::saveGeoCoordinates($this->arrCatalog['table'], $objDataContainer->activeRecord->row());
                }
            };
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['config']['hasVisibilityFields'] = $this->arrCatalog['enableVisibility'] ? true : false;
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

        if ($this->arrCatalog['sortingType']) {
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
                        $arrList['sorting']['fields'][] = $arrOrder['field'] . ( $arrOrder['order'] ? ' ' . $arrOrder['order'] : '' );
                        $arrSortingFields[] = $arrOrder['field'];
                    }
                }
                if ( empty( $arrList['labels']['fields'] ) ) {
                    $arrList['labels']['fields'] = $arrSortingFields;
                }
            }
        }

        if (count($arrList['labels']['fields']) > 0) {
            $arrList['labels']['label_callback'] = function ($arrRow, $strLabel, \DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false ) use ( $arrList ) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };
        }

        if ( $this->arrCatalog['mode'] == 'parent' ) {
            $arrList['sorting']['mode'] = 4;
            $arrList['sorting']['headerFields'] = empty($this->arrCatalog['headerFields']) ? ['id'] : $this->arrCatalog['headerFields'];
            $arrList['sorting']['child_record_callback'] =  function ( $arrRow ) use ( $arrList ) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };

            $arrList['labels']['showColumns'] = false;
        }

        if ( $this->arrCatalog['mode'] == 'tree' ) {
            $arrList['sorting']['mode'] = 5;
            $arrList['sorting']['fields'] = ['sorting'];
            $arrList['sorting']['icon'] = 'articles.svg'; // @todo icon
            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
            $arrList['labels']['label_callback'] =  function ( $arrRow, $strLabel, \DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false  ) use ( $arrList ) {
                return Toolkit::renderTreeRow( $arrRow, $strLabel, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields );
            };
            $arrList['sorting']['fields'] = [];
            $arrList['labels']['showColumns'] = false;
            array_insert( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'], 1, [
                'cut' => [
                    'icon' => 'cut.svg',
                    'href' => 'act=paste&amp;mode=cut',
                    'attributes' => 'onclick="Backend.getScrollOffset()"'
                ]
            ]);
        }

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['label'] = $arrList['labels'];
        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['sorting'] = $arrList['sorting'];

        if ( $this->arrCatalog['enableCopy'] ) {
            array_insert( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'], 1, [
                'copy' => [
                    'href' => 'act=copy',
                    'icon' => 'copy.gif'
                ]
            ]);
        }

        if ( $this->arrCatalog['enableVisibility'] ) {
            array_insert( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'], count( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'] ) - 1, [
                'toggle' => [
                    'icon' => 'visible.gif',
                    'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s,\''.$this->arrCatalog['table'].'\')"',
                    'button_callback' => [ 'catalogmanager.datacontainer.catalog', 'toggleIcon' ],
                    'showInHeader' => true
                ]
            ]);
        }
    }

    protected function setFields() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['fields'] = $this->arrFields;
    }

    protected function setPalettes() {

        // @todo implement palettes builder with extra table for configs…
        $arrPalette = [];

        foreach ( $this->arrFields as $strFieldname => $arrField ) {

            if ( in_array( $this->arrFields['type'], ['empty'] ) ) {
                continue;
            }

            if ( !$this->arrCatalog['enableVisibility'] && in_array( $strFieldname, [ 'published', 'start', 'stop' ] ) ) {
                continue;
            }

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
                \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.' . $strFieldname, $arrField['name'] ),
                \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description' . $strFieldname, $arrField['name'] )
            ];
        }
    }

    protected function generateEmptyDataContainer() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ] = [
            'config' => [
                'onsubmit_callback' => [
                    function( \DataContainer $objDataContainer ) {
                        if ($objDataContainer->activeRecord) {
                            Toolkit::saveAlias($objDataContainer->activeRecord->row(), $this->arrFields, $this->arrCatalog);
                        }
                    }
                ],
                'sql' => [
                    'keys' => [
                        'id' => 'primary'
                    ]
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
            $strTitle = '';
            $strDescription = '';
            $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule($strTable);
            if ( $objCatalog !== null ) {
                $strTitle = $objCatalog->name;
                $strDescription = $objCatalog->description;
            }
            $arrOperation = [];
            $arrOperation[ 'child_' . $strTable ] = [
                'label' => [
                    \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate('child_' . $strTable . '.title', $strTitle),
                    \Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate('child_' . $strTable . '.description', ($strDescription ?:$strTitle)),
                ],
                'href' => 'table=' . $strTable,
                'icon' => 'edit.gif'
            ];
            array_insert( $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ]['list']['operations'], 1, $arrOperation );
        }
    }

    public function generate() {

        if ( empty( $this->arrCatalog ) ) {
            return null;
        }

        $this->setConfig();
        $this->setList();
        $this->setOperations();
        $this->setPalettes();
        $this->setSubPalettes();
        $this->setFields();
        $this->setLabels();

        if (isset($GLOBALS['TL_HOOKS']['loadVirtualDataContainer']) && is_array($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'] as $arrCallback) {
                $this->import($arrCallback[0]);
                $this->{$arrCallback[0]}->{$arrCallback[1]}($this->arrCatalog['table'], $this);
            }
        }
    }
}