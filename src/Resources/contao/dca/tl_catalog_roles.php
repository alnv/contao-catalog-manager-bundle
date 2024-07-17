<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_catalog_roles'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
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
            'panelLayout' => 'filter,search'
        ],
        'label' => [
            'fields' => ['name'],
            'showColumns' => true
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
        'default' => 'name;maxlength,minlength;maxval,minval;class;rgxp;sql'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'alias',
                'maxlength' => 64,
                'doNotCopy' => true,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'sql' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => 'varchar(255) NOT NULL default \'\'']
        ],
        'maxlength' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 16,
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '0']
        ],
        'minlength' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 16,
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '0']
        ],
        'minval' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 16,
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '0']
        ],
        'maxval' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 16,
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '0']
        ],
        'class' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 128,
                'tl_class' => 'clr long',
                'decodeEntities' => true
            ],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'rgxp' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 32,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ]
    ]
];