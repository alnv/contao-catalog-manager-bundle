<?php

use Contao\Config;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

$GLOBALS['TL_DCA']['tl_catalog_reactions'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'backendSearchIgnore' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 0
        ],
        'label' => [
            'fields' => ['name']
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
                'primary' => true
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
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => 'name,reactions,template',
    ],
    'subpalettes' => [],
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
                'maxlength' => 128,
                'doNotCopy' => true,
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'reactions' => [
            'inputType' => 'rowWizard',
            'eval' => [
                'tl_class' => 'clr w50',
                'actions' => [
                    'copy',
                    'delete'
                ]
            ],
            'fields' => [
                'name' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_catalog_reactions']['name'],
                    'inputType' => 'text',
                    'eval' => [
                        'style' => 'width:150px',
                        'mandatory' => true
                    ]
                ],
                'key' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_catalog_reactions']['key'],
                    'inputType' => 'text',
                    'eval' => [
                        'style' => 'width:150px',
                        'mandatory' => true,
                        'rgxp' => 'alias'
                    ]
                ],
                'icon' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_catalog_reactions']['icon'],
                    'inputType' => 'fileTree',
                    'eval' => [
                        'filesOnly' => true,
                        'fieldType' => 'radio',
                        'extensions' => Config::get('validImageTypes'),
                        'tl_class' => 'clr'
                    ]
                ]
            ],
            'sql' => [
                'type' => 'blob',
                'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB,
                'notnull' => false,
            ]
        ],
        'template' => [
            'inputType' => 'select',
            'eval' => [
                'mandatory' => true,
                'tl_class' => 'w50 clr'
            ],
            'options_callback' => function () {
                return $this->getTemplateGroup('reactions_');
            },
            'sql' => "varchar(64) NOT NULL default ''"
        ]
    ]
];