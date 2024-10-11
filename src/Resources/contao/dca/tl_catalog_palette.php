<?php

use Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogPalette;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Controller;
use Contao\StringUtil;
use Contao\DC_Table;
use Contao\Database;
use Contao\Widget;

$GLOBALS['TL_DCA']['tl_catalog_palette'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'ptable' => 'tl_catalog',
        'onload_callback' => [
            function (DataContainer $objDataContainer) {

                $objCurrent = CatalogPaletteModel::findByPk($objDataContainer->id);
                if (!$objCurrent) {
                    return null;
                }

                if ($objCurrent->type == 'subpalette' && $objCurrent->selector) {
                    $arrOptions = (new CatalogPalette())->getFieldOptions($objCurrent->selector);
                    if (!empty($arrOptions)) {
                        PaletteManipulator::create()
                            ->addField('selector_option', 'selector')
                            ->applyToPalette('subpalette', 'tl_catalog_palette');
                        $GLOBALS['TL_DCA']['tl_catalog_palette']['fields']['selector_option']['options'] = $arrOptions;
                    }
                }

                $objCatalog = CatalogModel::findByPk($objCurrent->pid);

                if ($objCatalog) {

                    $strTable = $objCatalog->table;

                    Controller::loadDataContainer($strTable);

                    if (!empty($GLOBALS['TL_DCA'][$strTable]['fields']['type'])) {

                        $arrTypeAttributes = Widget::getAttributesFromDca($GLOBALS['TL_DCA'][$strTable]['fields']['type'], 'type', '', 'type', $strTable, $objDataContainer);
                        $arrOptions = [];
                        foreach (($arrTypeAttributes['options'] ?? []) as $arrOption) {
                            if (!$arrOption['value']) {
                                continue;
                            }
                            $arrOptions[$arrOption['value']] = $arrOption['label'] ?? '-';
                        }

                        PaletteManipulator::create()
                            ->addField('selector_type', 'name')
                            ->applyToPalette('palette', 'tl_catalog_palette');

                        $GLOBALS['TL_DCA']['tl_catalog_palette']['fields']['selector_type']['options'] = $arrOptions;
                    }
                }

                $GLOBALS['TL_DCA']['tl_catalog_palette']['fields']['fields']['eval']['columnFields']['field']['options'] = (new CatalogPalette())->getFieldsByCatalogId($objCurrent->pid, $objCurrent->type);
            }
        ],
        'onsubmit_callback' => [
            function (DataContainer $objDataContainer) {

                $arrFields = [];
                $intPosition = 0;
                $arrFieldSets = StringUtil::deserialize($objDataContainer->activeRecord->fieldsets, true);

                foreach (StringUtil::deserialize($objDataContainer->activeRecord->fields, true) as $arrField) {
                    if ($arrField['field'] === '__FIELDSET__') {
                        $intPosition++;
                        $arrFields[] = 'Fieldset ' . $intPosition;
                    }
                }

                if (\count($arrFieldSets) != \count($arrFields)) {
                    $arrNewSets = [];
                    foreach ($arrFields as $strIndex => $strField) {
                        $strLabel = $strField;
                        if (isset($arrFieldSets[$strIndex])) {
                            $strLabel = $arrFieldSets[$strIndex]['label'] ?: $strField;
                        }
                        $arrNewSets[] = [
                            'label' => $strLabel,
                            'hide' => $arrFieldSets[$strIndex]['hide'] ? '1' : ''
                        ];
                    }

                    Database::getInstance()
                        ->prepare('UPDATE tl_catalog_palette %s WHERE id=?')
                        ->set([
                            'tstamp' => \time(),
                            'fieldsets' => \serialize($arrNewSets)
                        ])
                        ->limit(1)
                        ->execute($objDataContainer->id);
                }
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
            'headerFields' => ['name', 'table'],
            'child_record_callback' => function ($arrRow) {
                $arrTypes = $GLOBALS['TL_LANG']['tl_catalog_palette']['reference']['type'] ?: [];
                return $arrRow['name'] .
                    (isset($arrTypes[$arrRow['type']]) ? ' <span style="background:#f0c674;color:#fff;border-radius:5px;padding:3px;font-size:12px;">' . $arrTypes[$arrRow['type']] . '</span>' : '');
            }
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'showInHeader' => true
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
        '__selector__' => ['type'],
        'default' => 'type,name',
        'palette' => 'type,name,fields,fieldsets,published',
        'subpalette' => 'type,name,selector,fields,published'
    ],
    'subpalettes' => [],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0]
        ],
        'type' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 16,
                'tl_class' => 'w50',
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options' => ['palette', 'subpalette'],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_palette']['reference']['type'],
            'filter' => true,
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '']
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
        'selector_type' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'includeBlankOption' => true
            ],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'selector' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options_callback' => ['catalogmanager.datacontainer.catalogpalette', 'getFields'],
            'filter' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'selector_option' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
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
                            'style' => 'width:100%;max-width:375px',
                            'includeBlankOption' => true
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
                            'style' => 'width:100%;max-width:400px'
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
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'doNotCopy' => true
            ],
            'filter' => true,
            'toggle' => true,
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];