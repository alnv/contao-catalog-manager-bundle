<?php

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_catalog_reactions_data'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'table,session,member,identifier,reaction' => 'index',
            ]
        ]
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'created_at' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'session' => [
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'member' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'table' => [
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'identifier' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'reaction' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'reaction_key' => [
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ]
    ]
];