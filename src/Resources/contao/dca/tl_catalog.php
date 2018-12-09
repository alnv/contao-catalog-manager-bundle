<?php

$GLOBALS['TL_DCA']['tl_catalog'] = [

    'config' => [

        'dataContainer' => 'Table',
        'ctable' => [

            'tl_catalog_field'
        ],
        'onsubmit_callback' => [

            [ 'catalogmanager.datacontainer.catalog', 'watchOnTable' ]
        ],
        'sql' => [

            'keys' => [

                'id' => [

                    'id' => 'primary',
                    'table' => 'index'
                ]
            ]
        ]
    ],

    'list' => [

        'sorting' => [

            'mode' => 2,
            'flag' => 1,
            'fields' => [ 'name' ],
            'panelLayout' => 'filter;sort,search,limit'
        ],

        'label' => [

            'showColumns' => true,
            'fields' => [ 'name', 'tablename' ]
        ],

        'operations' => [

            'fields' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog']['fields'],
                'href' => 'table=tl_catalog_field',
                'icon' => 'edit.gif'
            ],

            'edit' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog']['edit'],
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],

            'copy' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ],

            'delete' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],

            'show' => [

                'label' => &$GLOBALS['TL_LANG']['tl_catalog']['show'],
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

        '__selector__' => [ 'type', 'view', 'enableGeocoding' ],
        'default' => '{general_settings},name,type;',
        'catalog' => '{general_settings},name,type;{catalog_settings},table,enableContentElements;{view_settings},view,navigation,position,enableCopy,enableVisibility;{geocoding_module:hide},enableGeocoding',
        'subitem' => '{general_settings},name,type;{catalog_settings},table,parent,enableContentElements;{view_settings},parentList,sortable,orderBy,flag,enableCopy,enableMove,enableVisibility;{geocoding_module:hide},enableGeocoding',
        'core' => '{general_settings},name,type;{catalog_settings},table;'
    ],

    'subpalettes' => [

        'enableGeocoding' => 'geoCity,geoZip,geoStreet,geoStreetNumber,geoCountry',

        'view_0' => 'showColumns', // default
        'view_1' => 'showColumns,orderBy,flag',
        'view_2' => 'showColumns,orderBy,flag',
        'view_5' => '',
    ],

    'fields' => [

        'id' => [

            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
        ],

        'tstamp' => [

            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],

        'children' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['children'],
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],

        'module' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['module'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],

        'name' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['name'],
            'inputType' => 'text',
            'eval' => [

                'maxlength' => 128,
                'doNotCopy' => true,
                'tl_class' => 'w50',
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],

        'type' => [

            'label' =>  &$GLOBALS['TL_LANG']['tl_catalog']['type'],
            'inputType' => 'select',
            'default' => 'table',
            'eval' => [

                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'submitOnChange' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getCatalogTypes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['type'],
            'filter' => true,
            'exclude' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],

        'table' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['tablename'],
            'inputType' => 'text',
            'eval' => [

                'rgxp' => 'extnd',
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'spaceToUnderscore' => true,
            ],
            'save_callback' => [

                [ 'catalogmanager.datacontainer.catalog', 'watchTable' ]
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],

        'parent' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['parent'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'submitOnChange' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'search' => true,
            'filter' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],

        'view' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['view'],
            'inputType' => 'radio',
            'default' => '0',
            'eval' => [

                'maxlength' => 2,
                'tl_class' => 'clr',
                'mandatory' => true,
                'submitOnChange' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getViews' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['view'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 2, 'default' => '0']
        ],

        'orderBy' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['orderBy'],
            'inputType' => 'text',
            'eval' => [

                'tl_class' => 'clr w50',
            ],
            // @todo order by widget
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],

        'flag' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['flag'],
            'inputType' => 'select',
            'eval' => [

                'maxlength' => 2,
                'tl_class' => 'w50',
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFlags' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['flag'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 2, 'default' => '']
        ],

        'parentList' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['parentList'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getParentFields' ],
            'exclude' => true,
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],

        'list' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['list'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => true
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],

        'showColumns' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['showColumns'],
            'inputType' => 'checkbox',
            'eval' => [

                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'navigation' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['navigation'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50'
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getNavigation' ],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],

        'position' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['position'],
            'inputType' => 'text',
            'eval' => [

                'maxlength' => 4,
                'tl_class' => 'w50'
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 4, 'default' => '']
        ],

        'enableMove' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['enableMove'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableCopy' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['enableCopy'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableVisibility' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['enableVisibility'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableContentElements' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['enableContentElements'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'w50 m12',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'sortable' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['sortable'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableGeocoding' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['enableGeocoding'],
            'inputType' => 'checkbox',
            'eval' => [

                'tl_class' => 'clr',
                'multiple' => false,
                'submitOnChange' => true
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'geoCity' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['geoCity'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'fixed' => true, 'default' => '']
        ],

        'geoZip' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['geoZip'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'fixed' => true, 'default' => '']
        ],

        'geoStreet' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['geoStreet'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'fixed' => true, 'default' => '']
        ],

        'geoStreetNumber' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['geoStreetNumber'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'fixed' => true, 'default' => '']
        ],

        'geoCountry' => [

            'label' => &$GLOBALS['TL_LANG']['tl_catalog']['geoCountry'],
            'inputType' => 'select',
            'eval' => [

                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50'
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'fixed' => true, 'default' => '']
        ]
    ]
];