<?php

use Contao\Input;
use Contao\DC_Table;
use Contao\DataContainer;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;

$GLOBALS['TL_DCA']['tl_page_filter'] = [
    'config' => [
        'closed' => true,
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'ptable' => 'tl_page',
        'onsubmit_callback' => [],
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
            'headerFields' => ['type', 'title'],
            'child_record_callback' => function ($arrRow) {
                return $arrRow['table'] . ' - ' . $arrRow['column'];
            }
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => ['type'],
        'default' => 'type',
        'routing_table' => 'type;table,column'
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
                'maxlength' => 32,
                'tl_class' => 'w50',
                'mandatory' => true,
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options' => ['routing_table'],
            'reference' => &$GLOBALS['TL_LANG']['tl_page_filter'],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'table' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options_callback' => function () {
                $arrReturn = [];
                $objCatalogs = CatalogModel::findAll();
                if (!$objCatalogs) {
                    return $arrReturn;
                }
                while ($objCatalogs->next()) {
                    $arrReturn[] = $objCatalogs->table;
                }
                return $arrReturn;
            },
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'column' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'includeBlankOption' => true
            ],
            'options_callback' => function (DataContainer $objDataContainer) {
                $arrReturn = [];
                $arrActiveRecord = Toolkit::getActiveRecordAsArrayFromDc($objDataContainer);
                if (!$arrActiveRecord['table']) {
                    return $arrReturn;
                }
                $objCatalog = CatalogModel::findByTableOrModule($arrActiveRecord['table']);
                if (!$objCatalog) {
                    return $arrReturn;
                }
                $objCatalogFields = CatalogFieldModel::findByParent($objCatalog->id);
                if (!$objCatalogFields) {
                    return $arrReturn;
                }
                while ($objCatalogFields->next()) {
                    $arrReturn[] = $objCatalogFields->fieldname;
                }
                return $arrReturn;
            },
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ]
    ]
];

if (Input::get('dcaWizard')) {
    $GLOBALS['TL_DCA']['tl_page_filter']['config']['closed'] = false;
    $GLOBALS['TL_DCA']['tl_page_filter']['list']['sorting']['filter'] = [['pid=?', Input::get('id')]];
}