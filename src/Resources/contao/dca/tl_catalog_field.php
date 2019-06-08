<?php

$GLOBALS['TL_DCA']['tl_catalog_field'] = [

    'config' => [

        'dataContainer' => 'Table',
        'ptable' => 'tl_catalog',

        'sql' => [

            'keys' => [

                'id' => 'primary',
                'pid' => 'index',
                'fieldname' => 'index'
            ]
        ]
    ],

    'list' => [

        'sorting' => [

            'mode' => 4,
            'fields' => [ 'sorting' ],
            'headerFields' => [ 'type', 'name', 'table' ],
        ],

        'operations' => [

            'edit' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['edit'],
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],

            'copy' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ],

            'delete' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],

            'toggle' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['toggle'],
                'icon' => 'visible.gif',
                'href' => sprintf( 'catalogTable=%s', 'tl_catalog_fields' ),
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s, '. sprintf( "'%s'", 'tl_catalog_fields' ) .' )"',
                'button_callback' => [ 'CatalogManager\DcCallbacks', 'toggleIcon' ]
            ],

            'show' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['show'],
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

        '__selector__' => [ 'type' ],
        'default' => '{general_settings},name,type',
        'text' => '{general_settings},name,type;{field_settings},fieldname,role',
        'textarea' => '{general_settings},name,type;{field_settings},fieldname,role',
        'select' => '{general_settings},name,type;{field_settings},fieldname,role',
        'radio' => '{general_settings},name,type;{field_settings},fieldname,role',
        'checkbox' => '{general_settings},name,type;{field_settings},fieldname,role',
        'upload' => '{general_settings},name,type;{field_settings},fieldname,role'
    ],

    'subpalettes' => [

        //
    ],

    'fields' => [

        'id' => [

            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
        ],

        'pid' => [

            'foreignKey' => 'tl_catalog.id',
            'relation' => [

                'type' => 'belongsTo',
                'load' => 'lazy'
            ],
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],

        'sorting' => [

            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],

        'tstamp' => [

            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],

        'type' => [

            'label' =>  &$GLOBALS['TL_LANG']['tl_catalog_field']['type'],
            'inputType' => 'select',
            'default' => 'table',
            'eval' => [

                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'submitOnChange' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFieldTypes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_field']['reference']['type'],
            'filter' => true,
            'exclude' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],

        'name' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['name'],
            'inputType' => 'text',
            'eval' => [

                'maxlength' => 64,
                'tl_class' => 'w50',
                'mandatory' => true,
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],

        'role' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['role'],
            'inputType' => 'select',
            'eval' => [

                'maxlength' => 64,
                'tl_class' => 'w50',
                'mandatory' => true,
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getRoles' ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],

        'fieldname' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog_field']['fieldname'],
            'inputType' => 'text',
            'eval' => [

                'rgxp' => 'extnd',
                'maxlength' => 64,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'spaceToUnderscore' => true,
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ]
    ]
];