<?php

$GLOBALS['TL_DCA']['tl_catalog_option'] = [
    'config' => [
        'dataContainer' => 'Table',
        'closed' => true,
        'onsubmit_callback' => [
            function( \DataContainer $objDataContainer ) {
                if (!$objDataContainer->activeRecord) {
                    return null;
                }
                $arrSet = [];
                $arrSet['tstamp'] = time();
                $arrSet['value'] = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::generateAlias($objDataContainer->activeRecord->label, 'value', 'tl_catalog_option', $objDataContainer->activeRecord->id);
                \Database::getInstance()->prepare( 'UPDATE tl_catalog_option %s WHERE id=?' )->set($arrSet)->execute($objDataContainer->activeRecord->id);
            },
            function( \DataContainer $objDataContainer ) {
                if (!$objDataContainer->activeRecord || !\Input::get('dcaWizard')) {
                    return null;
                }
                $arrSet = [];
                $arrSet['tstamp'] = time();
                $arrSet['pid'] = \Input::get('dcaWizard');
                \Database::getInstance()->prepare( 'UPDATE tl_catalog_option %s WHERE id=?' )->set($arrSet)->execute($objDataContainer->activeRecord->id);
            }
        ],
        'onload_callback' => [],
        'sql' => [
            'keys' => [
                'id' => [
                    'id' => 'primary',
                    'pid' => 'index',
                    'value' => 'index'
                ]
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'flag' => 4,
            'fields' => [ 'pid' ],
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => [ 'label' ]
        ],
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
        'global_operations' => []
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => 'label,value'
    ],
    'subpalettes' => [],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
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
        'label' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
                'allowHtml' => true
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
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
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ]
    ]
];

if (\Input::get('dcaWizard')) {
    $GLOBALS['TL_DCA']['tl_catalog_option']['config']['closed'] = false;
    $GLOBALS['TL_DCA']['tl_catalog_option']['list']['sorting']['filter'] = [['pid=?',\Input::get('id')]];
}