<?php

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_catalog_option'] = [
    'config' => [
        'closed' => true,
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'ptable' => 'tl_catalog_field',
        'onsubmit_callback' => [
            function (DataContainer $objDataContainer) {
                if (!$objDataContainer->activeRecord || !Input::get('dcaWizard')) {
                    return null;
                }
                $arrSet = [];
                $arrSet['tstamp'] = time();
                $arrSet['pid'] = Input::get('dcaWizard');
                Database::getInstance()->prepare('UPDATE tl_catalog_option %s WHERE id=?')->set($arrSet)->execute($objDataContainer->activeRecord->id);
            },
            function (DataContainer $objDataContainer) {
                if (!$objDataContainer->activeRecord) {
                    return null;
                }
                $objActive = Database::getInstance()->prepare('SELECT * FROM tl_catalog_option WHERE id=?')->limit(1)->execute($objDataContainer->activeRecord->id);
                $arrSet = [];
                $arrSet['tstamp'] = time();
                $arrSet['value'] = Toolkit::generateAlias($objActive->label, 'value', 'tl_catalog_option', $objDataContainer->activeRecord->id, $objActive->pid, 'a-z0-9');
                Database::getInstance()->prepare('UPDATE tl_catalog_option %s WHERE id=?')->set($arrSet)->execute($objDataContainer->activeRecord->id);
            }
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,value' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['name', 'type', 'fieldname', 'role'],
            'child_record_callback' => function ($arrRow) {
                return $arrRow['label'] . '<span style="color:#999;padding-left:3px">[' . $arrRow['value'] . ']</span>';
            }
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
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
        'default' => 'label,value'
    ],
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
            'options_callback' => ['catalogmanager.datacontainer.catalogoption', 'getFieldLabels'],
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0]
        ],
        'label' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
                'allowHtml' => true
            ],
            'search' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'value' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 128,
                'tl_class' => 'w50',
                'doNotCopy' => true,
                'decodeEntities' => true
            ],
            'search' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ]
    ]
];

if (Input::get('dcaWizard')) {
    $GLOBALS['TL_DCA']['tl_catalog_option']['config']['closed'] = false;
    $GLOBALS['TL_DCA']['tl_catalog_option']['list']['sorting']['filter'] = [['pid=?', Input::get('id')]];
}