<?php

$GLOBALS['TL_DCA']['tl_catalog'] = [
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => [
            'tl_catalog_field'
        ],
        'onsubmit_callback' => [
           [ 'catalogmanager.datacontainer.catalog', 'generateModulename' ]
        ],
        'ondelete_callback' => [
            [ 'catalogmanager.datacontainer.catalog', 'deleteTable' ]
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'table' => 'index',
                'module' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 5,
            'fields' => [ 'name' ],
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'showColumns' => true,
            'fields' => [ 'name', 'tablename' ]
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],
            'fields' => [
                'href' => 'table=tl_catalog_field',
                'icon' => 'edit.gif'
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
        '__selector__' => [ 'type', 'mode', 'showColumns', 'enableGeocoding' ],
        'default' => '{type_settings},type;',
        'catalog' => '{type_settings},type;{general_settings},name,description;{catalog_settings},table;{mode_settings},mode;{extended_settings},enableCopy,enableVisibility,enablePanel,enableContentElements;{navigation_settings},navigation,position;{geocoding_settings:hide},enableGeocoding',
        'modifier' => '{type_settings},type;{general_settings},name;'
    ],
    'subpalettes' => [
        'enableGeocoding' => 'geoCity,geoZip,geoStreet,geoStreetNumber,geoCountry',
        'showColumns' => 'columns',
        'mode_none' => 'showColumns',
        'mode_fixed' => 'showColumns,order',
        'mode_flex' => 'showColumns,flag',
        'mode_custom' => 'showColumns',
        'mode_tree'=> ''
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0 ]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'module' => [
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'name' => [
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
        'description' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 120,
                'doNotCopy' => true,
                'tl_class' => 'w50',
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 120, 'default' => '']
        ],
        'type' => [
            'inputType' => 'select',
            'default' => 'catalog',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'submitOnChange' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getCatalogTypes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['type'],
            'filter' => true,
            'exclude' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'table' => [
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'extnd',
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'spaceToUnderscore' => true
            ],
            'save_callback' => [
                [ 'catalogmanager.datacontainer.catalog', 'watchTable' ]
            ],
            'search' => true,
            'sorting' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'mode' => [
            'inputType' => 'select',
            'default' => 'none',
            'eval' => [
                'chosen' => true,
                'maxlength' => 12,
                'tl_class' => 'w50',
                'mandatory' => true,
                'submitOnChange' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getModes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['mode'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 12, 'default' => 'none']
        ],
        'order' => [
            'inputType' => 'comboWizard',
            'eval' => [
                'tl_class' => 'clr',
                'options2_callback' => [ 'catalogmanager.datacontainer.catalog', 'getOrderByStatements' ]
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],
        'flag' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 2,
                'tl_class' => 'w50',
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFlags' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog']['reference']['flag'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 2, 'default' => '']
        ],
        'parentList' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getParentFields' ],
            'exclude' => true,
            'sql' => ['type' => 'blob', 'notnull' => false ]
        ],
        'showColumns' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false,
                'submitOnChange' => true
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'columns' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => true
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getFields' ],
            'sql' => [ 'type' => 'blob', 'notnull' => false  ]
        ],
        'navigation' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'exclude' => true,
            'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getNavigation' ],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'position' => [
            'inputType' => 'text',
            'default' => '0',
            'eval' => [
                'maxlength' => 4,
                'tl_class' => 'w50'
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 4, 'default' => '']
        ],
        'enableCopy' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enablePanel' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],
        'enableVisibility' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableContentElements' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 1, 'fixed' => true, 'default' => '']
        ],

        'enableGeocoding' => [
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