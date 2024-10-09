<?php

use Alnv\ContaoCatalogManagerBundle\DataContainer\Catalog;
use Alnv\ContaoCatalogManagerBundle\Entity\CatalogVectorFile;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Contao\Database;
use Contao\Input;
use Contao\Environment;

System::loadLanguageFile('tl_catalog_field');

$GLOBALS['TL_DCA']['tl_catalog_vector_files'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'onsubmit_callback' => [function (DataContainer $objDataContainer) {
            $objSearchVectorFile = new CatalogVectorFile($objDataContainer->id);
            $strUuid = $objSearchVectorFile->save('files/_vectors');
            Database::getInstance()
                ->prepare('UPDATE tl_catalog_vector_files %s WHERE id=?')
                ->set(['file' => $strUuid])
                ->limit(1)
                ->execute($objDataContainer->id);
        }],
        'onload_callback' => [function (DataContainer $objDataContainer) {
            if (!Input::get('vector_files')) {
                return;
            }
            if (Input::get('vector_files') == 'update') {
                $objSearchVectorFile = new CatalogVectorFile($objDataContainer->id);
                $objSearchVectorFile->update();
            }
            Controller::redirect(preg_replace('/&(amp;)?vector_files=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote(Input::get('vector_files'), '/') . '=[^&]*/i', '', Environment::get('request'))));
        }],
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['name'],
            'panelLayout' => 'filter,search;sort,limit'
        ],
        'label' => [
            'fields' => ['name', 'file'],
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg'
            ],
            'update' => [
                'icon' => 'sync.svg',
                'href' => 'vector_files=update',
                'attributes' => 'onclick="if(!confirm(\'Soll der Vector Store aktualisiert werden?\'))return false;Backend.getScrollOffset()"'
            ]
        ]
    ],
    'palettes' => [
        'default' => 'name,dbTable,fields;dbWizardFilterSettings;masterPage,template'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'tstamp' => [
            'flag' => 6,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'unique' => true,
                'decodeEntities' => true
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'dbTable' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
                'maxlength' => 128,
                'submitOnChange' => true,
                'includeBlankOption' => true,
                'mandatory' => true
            ],
            'options_callback' => function () {
                $objCatalogs = CatalogModel::findAll();
                if (!$objCatalogs) {
                    return [];
                }
                $arrCatalogs = [];
                while ($objCatalogs->next()) {
                    $arrCatalogs[$objCatalogs->table] = $objCatalogs->name;
                }
                return $arrCatalogs;
            },
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'fields' => [
            'inputType' => 'checkboxWizard',
            'eval' => [
                'multiple' => true,
                'tl_class' => 'clr'
            ],
            'options_callback' => function (DataContainer $objDataContainer) {
                $objDataContainer->activeRecord->table = $objDataContainer->activeRecord->dbTable;
                $arrFields = [];
                foreach ((new Catalog())->getFields($objDataContainer) as $strField => $strLabel) {
                    if (in_array($strField, ['id', 'tstamp', 'sorting', 'lid', 'pid', 'published', 'alias', 'start', 'stop'])) {
                        continue;
                    }
                    $arrFields[$strField] = $strLabel;
                }
                return $arrFields;
            },
            'sql' => 'blob NULL'
        ],
        'dbWizardFilterSettings' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['optionSourceDbWizardFilterSettings'],
            'inputType' => 'comboWizard',
            'eval' => [
                'tl_class' => 'clr',
                'mandatory' => false,
                'options2_callback' => ['catalogmanager.datacontainer.catalog', 'getOperators'],
                'enableField' => true,
                'enableGroup' => true
            ],
            'options_callback' => ['catalogmanager.datacontainer.catalog', 'getDbFields'],
            'sql' => ['type' => 'blob', 'notnull' => false]
        ],
        'masterPage' => [
            'inputType' => 'pageTree',
            'eval' => [
                'tl_class' => 'w50 clr'
            ],
            'foreignKey' => 'tl_page.title',
            'relation' => [
                'load' => 'lazy',
                'type' => 'hasOne'
            ],
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'template' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 255,
                'tl_class' => 'w50',
                'mandatory' => false,
                'includeBlankOption' => true
            ],
            'options_callback' => function () {
                return Controller::getTemplateGroup('cm_listing_');
            },
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'file' => [
            'sql' => 'blob NULL'
        ]
    ]
];