<?php

$GLOBALS['TL_DCA']['tl_catalog_data'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'type,table,session,member,identifier' => 'index'
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
        'type' => [
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '']
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
        ]
    ]
];