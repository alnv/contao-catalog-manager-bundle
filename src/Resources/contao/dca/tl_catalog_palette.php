<?php

$GLOBALS['TL_DCA']['tl_catalog_palette'] = [
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'ptable' => 'tl_catalog',
        'onload_callback' => [
            function (\DataContainer $objDataContainer) {
                $objCurrent = \Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel::findByPk($objDataContainer->id);
                if (!$objCurrent) {
                    return null;
                }
                $GLOBALS['TL_DCA']['tl_catalog_palette']['fields']['fields']['eval']['columnFields']['field']['options'] = (new \Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogPalette())->getFieldsByCatalogId($objCurrent->pid);
            }
        ],
        'onsubmit_callback' => [
            function (\DataContainer $objDataContainer) {

                $arrFields = [];
                $intPosition = 0;
                $arrSubpalettes = [];
                $arrNewSubpalettes = [];
                $arrFieldsets = \StringUtil::deserialize($objDataContainer->activeRecord->fieldsets, true);

                foreach (\StringUtil::deserialize($objDataContainer->activeRecord->subpalettes, true) as $arrSubpalette) {
                    $arrSubpalettes[$arrSubpalette['subpalette']] = $arrSubpalette;
                }

                foreach (\StringUtil::deserialize($objDataContainer->activeRecord->fields, true) as $arrField) {

                    if ($arrField['field'] === '__FIELDSET__') {

                        $intPosition++;
                        $arrFields[] = 'Fieldset ' . $intPosition;
                    }

                    if ($arrField['subpalette']) {

                        $objCatalogField = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByPk($arrField['field']);
                        $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByPk($objDataContainer->activeRecord->pid);

                        if (!$objCatalog || !$objCatalogField) {
                            continue;
                        }

                        \Controller::loadDataContainer($objCatalog->table);

                        $arrField = $GLOBALS['TL_DCA'][$objCatalog->table]['fields'][$objCatalogField->fieldname];
                        $arrAttribute = \Widget::getAttributesFromDca($arrField, $objCatalogField->fieldname, '', $objCatalogField->fieldname, $objCatalog->table);

                        if (isset($arrAttribute['options']) && is_array($arrAttribute['options']) && $objCatalogField->optionsSource) {
                            foreach ($arrAttribute['options'] as $arrOption) {
                                if (!$arrOption['value']) {
                                    continue;
                                }
                                $arrNewSubpalettes[] = [
                                    'subpalette' => $arrOption['value'],
                                    'fields' => $arrSubpalettes[$arrOption['value']]['fields'] ?: [],
                                ];
                            }
                        } else {
                            $arrNewSubpalettes[] = [
                                'subpalette' => $objCatalogField->fieldname,
                                'fields' => $arrSubpalettes[$objCatalogField->fieldname]['fields'] ?: [],
                            ];
                        }
                    }
                }

                $objCurrent = \Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel::findByPk($objDataContainer->id);
                $objCurrent->subpalettes = serialize($arrNewSubpalettes);

                if (count($arrFieldsets) != count($arrFields)) {
                    $arrNewSets = [];
                    foreach ($arrFields as $strIndex => $strField) {
                        $strLabel = $strField;
                        if (isset($arrFieldsets[$strIndex])) {
                            $strLabel = $arrFieldsets[$strIndex]['label'] ?: $strField;
                        }
                        $arrNewSets[] = [
                            'label' => $strLabel,
                            'hide' => $arrFieldsets[$strIndex]['hide'] ? '1' : ''
                        ];
                    }
                    $objCurrent->fieldsets = serialize($arrNewSets);
                }

                $objCurrent->save();
            }
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['id', 'name', 'table'],
            'child_record_callback' => function($arrRow) {
                return $arrRow['name'];
            }
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.svg'
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'toggle' => [
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['catalogmanager.datacontainer.catalog', 'toggleIcon']
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ],
    'palettes' => [
        'default' => 'name,fields,fieldsets,subpalettes,published'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 32,
                'tl_class' => 'w50',
                'mandatory' => true,
                'decodeEntities' => true
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'alias' => [
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'fields' => [
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'decodeEntities' => true,
                'submitOnChange' => true,
                'tl_class' => 'clr w50',
                'columnFields' => [
                    'field' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['field'],
                        'inputType' => 'select',
                        'eval' => [
                            'chosen' => true,
                            'style' => 'width:250px',
                            'includeBlankOption' => true
                        ]
                    ],
                    'cssClass' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['cssClass'],
                        'inputType' => 'select',
                        'eval' => [
                            'chosen' => true,
                            'multiple' => true,
                            'style' => 'width:250px',
                            'includeBlankOption' => true
                        ],
                        'options' => (new \Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogPalette())->getCssClasses()
                    ],
                    'subpalette' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['subpalette'],
                        'inputType' => 'checkbox',
                        'eval' => [
                            'multiple' => false
                        ]
                    ]
                ]
            ],
            'sql' => 'blob NULL',
        ],
        'fieldsets' => [
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'tl_class' => 'clr w50',
                'decodeEntities' => true,
                'buttons' => ['new' => false, 'copy' => false, 'delete' => false, 'up' => false, 'down' => false, 'move' => false],
                'columnFields' => [
                    'label' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['label'],
                        'inputType' => 'text',
                        'eval' => [
                            'style' => 'width:320px'
                        ]
                    ],
                    'hide' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['hide'],
                        'inputType' => 'checkbox',
                        'eval' => [
                            'multiple' => false
                        ]
                    ]
                ]
            ],
            'sql' => 'blob NULL'
        ],
        'subpalettes' => [
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'tl_class' => 'clr long',
                'decodeEntities' => true,
                'options2_callback' => ['catalogmanager.datacontainer.catalogpalette', 'getCssClasses'],
                'options_callback'=> ['catalogmanager.datacontainer.catalogpalette', 'getFields'],
                'buttons' => ['new' => false, 'copy' => false, 'delete' => false, 'up' => false, 'down' => false, 'move' => false],
                'columnFields' => [
                    'subpalette' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['subpalette'],
                        'inputType' => 'text',
                        'eval' => [
                            'style' => 'width:100%'
                        ]
                    ],
                    'fields' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['fields'],
                        'inputType' => 'comboWizard',
                        'eval' => [
                            'tl_class' => 'w50',
                            'decodeEntities' => true
                        ]
                    ]
                ]
            ],
            'sql' => 'blob NULL'
        ],
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'doNotCopy' => true
            ],
            'filter' => true,
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];