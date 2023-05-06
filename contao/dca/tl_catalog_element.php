<?php

$GLOBALS['TL_DCA']['tl_catalog_element'] = [
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_content'],
        'onsubmit_callback' => [],
        'onload_callback' => [],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 0,
            'fields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['type', 'title'],
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'icon' => 'edit.svg',
                'href' => 'table=tl_content'
            ],
            'editheader' => [
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
        '__selector__' => [],
        'default' => 'type,title'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'title' => [
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
        'type' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'mandatory' => true,
                'includeBlankOption' => true
            ],
            'options' => ['article'],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_element']['reference']['type'],
            'filter' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
    ]
];