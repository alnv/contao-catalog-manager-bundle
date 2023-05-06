<?php

$GLOBALS['TL_DCA']['tl_watchlist'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'table,session,member,identifier' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'flag' => 12,
            'fields' => ['created_at'],
            'panelLayout' => 'filter;sort,search'
        ],
        'label' => [
            'fields' => ['member', 'session', 'table', 'identifier', 'units', 'sent', 'created_at'],
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
        'default' => 'session,member,table,identifier,units,sent'
    ],
    'fields' => [
        'id' => [
            'search' => true,
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'tstamp' => [
            'sorting' => true,
            'flag' => 6,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'created_at' => [
            'sorting' => true,
            'flag' => 6,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'session' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'long clr',
                'maxlength' => 64,
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'member' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
                'includeBlankOption' => true
            ],
            'foreignKey' => 'tl_member.username',
            'relation' => ['type' => 'hasOne', 'load' => 'eager'],
            'filter' => true,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'sent' => [
            'inputType' => 'checkbox',
            'eval' => [
                'multiple' => false,
                'tl_class' => 'clr'
            ],
            'filter' => true,
            'sql' => ['type' => 'string', 'fixed' => true, 'length' => 1, 'default' => '']
        ],
        'table' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
                'maxlength' => 128,
            ],
            'filter' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'identifier' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
            ],
            'search' => true,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'units' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
            ],
            'sorting' => true,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ]
    ]
];