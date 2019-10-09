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
            'child_record_callback'   => [ 'catalogmanager.datacontainer.catalogfield', 'listFields' ]
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
            'toggle' => [
                'icon' => 'visible.gif',
                'href' => sprintf( 'catalogTable=%s', 'tl_catalog_fields' ),
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [ 'catalogmanager.datacontainer.catalogfield', 'toggleIcon' ],
                'showInHeader' => true
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
        '__selector__' => [ 'type', 'optionsSource' ],
        'default' => '{general_settings},name,type',
        'text' => '{general_settings},name,type;{field_settings},fieldname,role;{published_legend},published',
        'textarea' => '{general_settings},name,type;{field_settings},fieldname,role;{published_legend},published',
        'select' => '{general_settings},name,type;{field_settings},fieldname,role;{options_legend},optionsSource;{published_legend},published',
        'radio' => '{general_settings},name,type;{field_settings},fieldname,role;{options_legend},optionsSource;{published_legend},published',
        'checkbox' => '{general_settings},name,type;{field_settings},fieldname,role;{options_legend},optionsSource;{published_legend},published',
        'upload' => '{general_settings},name,type;{field_settings},fieldname,role;{published_legend},published'
    ],
    'subpalettes' => [
        'optionsSource_options' => '',
        'optionsSource_dbOptions' => ''
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
            'options_callback' => [ 'catalogmanager.datacontainer.catalogfield', 'getFieldTypes' ],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_field']['reference']['type'],
            'save_callback' => [
                [ 'catalogmanager.datacontainer.catalogfield', 'changeFieldType' ]
            ],
            'filter' => true,
            'exclude' => true,
            'sorting' => true,
            'sql' => ['type' => 'string', 'length' => 32, 'default' => '']
        ],
        'name' => [
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
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50',
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalogfield', 'getRoles' ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'fieldname' => [
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'extnd',
                'maxlength' => 64,
                'tl_class' => 'w50',
                'mandatory' => true,
                'doNotCopy' => true,
                'spaceToUnderscore' => true,
            ],
            'save_callback' => [
                [ 'catalogmanager.datacontainer.catalogfield', 'watchFieldname' ]
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'optionsSource' => [
            'inputType' => 'radio',
            'eval' => [
                'maxlength' => 64,
                'tl_class' => 'clr',
                'mandatory' => true,
                'submitOnChange' => true
            ],
            'options' => [ 'options', 'dbOptions' ],
            'reference' => $GLOBALS['TL_LANG']['tl_catalog_field']['reference']['optionsSource'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'doNotCopy' => true
            ],
            'flag' => 2,
            'exclude' => true,
            'filter' => true,
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];