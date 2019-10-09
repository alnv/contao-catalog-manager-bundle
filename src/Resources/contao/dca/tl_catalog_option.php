<?php

$GLOBALS['TL_DCA']['tl_catalog_option'] = [
    'config' => [
        'dataContainer' => 'Table',
        'onload_callback' => [
            [ 'catalogmanager.datacontainer.catalogoption', 'generatePidEntities' ]
        ],
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
            'mode' => 5,
            'fields' => [ 'label' ],
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'showColumns' => true,
            'fields' => [ 'label', 'value' ]
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.gif'
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
        '__selector__' => [],
        'default' => 'label,value'
    ],
    'subpalettes' => [],
    'fields' => [
        'id' => [
            // @todo watch for id in catalog field
            // 'save_callback' => [ [ 'catalogmanager.datacontainer.catalogoption', 'preventDuplicateId' ] ],
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
                'tl_class' => 'w50'
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'value' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50'
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ]
    ]
];