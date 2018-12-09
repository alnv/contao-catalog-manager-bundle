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

        'default' => 'fieldname'
    ],

    'subpalettes' => [

        //
    ],

    'fields' => [

        'id' => [

            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],

        'pid' => [

            'foreignKey' => 'tl_catalog.id',
            'relation' => [

                'type' => 'belongsTo',
                'load' => 'lazy'
            ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'sorting' => [

            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],

        'tstamp' => [

            'sql' => "int(10) unsigned NOT NULL default '0'"
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
            'sql' => "varchar(64) NOT NULL default ''"
        ]
    ]
];