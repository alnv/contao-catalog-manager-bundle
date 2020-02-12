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
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [ 'catalogmanager.datacontainer.catalog', 'toggleIcon' ],
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
        '__selector__' => [ 'type', 'optionsSource', 'includeBlankOption' ],
        'default' => '{general_settings},name,type',
        'empty' => '{general_settings},name,type,fieldname,role,useAsAlias,{published_legend},published',
        'text' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory,multiple,size;{published_legend},published',
        'date' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory;{published_legend},published',
        'color' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory;{published_legend},published',
        'textarea' => '{general_settings},name,type;{field_settings},fieldname,role,mandatory,rte;{published_legend},published',
        'select' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory,multiple,size;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'radio' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'checkbox' => '{general_settings},name,type;{field_settings},fieldname,role,useAsAlias,mandatory,multiple;{options_legend},optionsSource;{published_legend},published',
        'upload' => '{general_settings},name,type;{field_settings},fieldname,role,mandatory,multiple,imageSize;{frontend_legend},extensions,uploadFolder,useHomeDir,doNotOverwrite;{published_legend},published'
    ],
    'subpalettes' => [
        'includeBlankOption' => 'blankOptionLabel',
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
        'useAsAlias' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'role' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 64,
                'tl_class' => 'w50',
                'mandatory' => true,
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
                'submitOnChange' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options' => [ 'options', 'dbOptions' ],
            'reference' => $GLOBALS['TL_LANG']['tl_catalog_field']['reference']['optionsSource'],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'multiple' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
                'submitOnChange' => true
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'size' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
            'exclude' => true,
            'sql' => "smallint(5) unsigned NOT NULL default '0'"
        ],
        'imageSize' => [
            'inputType' => 'radio',
            'eval' => [
                'maxlength' => 64,
                'tl_class' => 'clr',
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'options_callback' => [ 'catalogmanager.datacontainer.catalogfield', 'getImageSizes' ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'mandatory' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'rte' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
                'multiple' => false
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'doNotCopy' => true
            ],
            'exclude' => true,
            'filter' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'includeBlankOption' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false,
                'submitOnChange' => true
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ],
        'blankOptionLabel' => [
            'inputType' => 'text',
            'default' => '-',
            'eval' => [
                'maxlength' => 128,
                'tl_class' => 'w50',
            ],
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '-']
        ],
        'extensions' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'extnd',
                'maxlength' => 255,
                'mandatory' => true,
                'tl_class' => 'w50'
            ],
            'save_callback' => [['catalogmanager.datacontainer.catalogfield', 'checkExtensions']],
            'sql' => "varchar(255) NOT NULL default 'jpg,jpeg,gif,png,pdf,doc,docx,xls,xlsx,ppt,pptx'"
        ],
        'uploadFolder' => [
            'inputType' => 'fileTree',
            'eval' => [
                'mandatory' => true,
                'fieldType' => 'radio',
                'tl_class' => 'clr'
            ],
            'exclude' => true,
            'sql' => "binary(16) NULL"
        ],
        'useHomeDir' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12'
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'doNotOverwrite' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12'
            ],
            'exclude' => true,
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];