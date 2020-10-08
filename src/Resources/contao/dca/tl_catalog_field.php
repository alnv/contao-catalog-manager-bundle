<?php

$GLOBALS['TL_DCA']['tl_catalog_field'] = [
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_catalog',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,fieldname' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['type', 'name', 'table'],
            'child_record_callback'   => ['catalogmanager.datacontainer.catalogfield', 'listFields']
        ],
        'label' => [
            'fields' => ['name'],
            'format' => '%s',
            'label_callback' => ['catalogmanager.datacontainer.catalog', 'addIcon']
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
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
        '__selector__' => [ 'type', 'optionsSource', 'includeBlankOption', 'dbFilterType', 'dbFilter' ],
        'default' => '{general_settings},name,type',
        'empty' => '{general_settings},name,type,fieldname,role,useAsAlias,{published_legend},published',
        'text' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,size;{published_legend},published',
        'date' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory;{published_legend},published',
        'color' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory;{published_legend},published',
        'textarea' => '{general_settings},name,type;{field_settings},fieldname,role,description,mandatory,rte;{published_legend},published',
        'select' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,csv,size;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'radio' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'checkbox' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,csv;{options_legend},optionsSource;{published_legend},published',
        'checkboxWizard' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,csv;{options_legend},optionsSource;{published_legend},published',
        'pagepicker' => '{general_settings},name,type;fieldname,role,mandatory,multiple,{published_legend},published',
        'upload' => '{general_settings},name,type;{field_settings},fieldname,role,description,mandatory,imageSize;{frontend_legend},extensions,imageWidth,imageHeight,uploadFolder,useHomeDir,doNotOverwrite;{published_legend},published'
    ],
    'subpalettes' => [
        'dbFilter' => 'dbFilterType',
        'dbFilterType_expert' => 'dbFilterColumn,dbFilterValue',
        'dbFilterType_wizard' => 'dbWizardFilterSettings',
        'includeBlankOption' => 'blankOptionLabel',
        'optionsSource_options' => 'optionsDcaWizard',
        'optionsSource_dbOptions' => \Alnv\ContaoCatalogManagerBundle\Helper\OptionSourcePalette::getPalette()
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
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => true, 'unsigned' => true, 'default' => 0]
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
                'includeBlankOption' => true
            ],
            'options_callback' => ['catalogmanager.datacontainer.catalogfield', 'getFieldTypes'],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_field']['reference']['type'],
            'save_callback' => [['catalogmanager.datacontainer.catalogfield', 'changeFieldType']],
            'filter' => true,
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
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'description' => [
            'inputType' => 'textarea',
            'eval' => [
                'tl_class' => 'clr',
                'rte' => 'tinyMCE',
                'allowHtml' => true
            ],
            'sql' => "text NULL"
        ],
        'useAsAlias' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
            ],
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
            'options_callback' => ['catalogmanager.datacontainer.catalogfield', 'getRoles'],
            'search' => true,
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
                ['catalogmanager.datacontainer.catalogfield', 'watchFieldname']
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'optionsSource' => [
            'inputType' => 'radio',
            'eval' => [
                'maxlength' => 64,
                'tl_class' => 'clr',
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options' => [ 'options', 'dbOptions' ],
            'reference' => $GLOBALS['TL_LANG']['tl_catalog_field']['reference']['optionsSource'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'optionsDcaWizard' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_catalog_option',
            'foreignField' => 'pid',
            'params' => [
                'dcaWizard'=> \Input::get('id')
            ],
            'eval' => [
                'showOperations' => true,
                'orderField' => 'sorting ASC',
                'operations' => ['edit','delete'],
                'fields' => ['id','label','value']
            ]
        ],
        'multiple' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr'
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'size' => [
            'inputType' => 'text',
            'eval' => [
                'tl_class' => 'w50',
                'rgxp' => 'natural'
            ],
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
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'mandatory' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'rte' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'doNotCopy' => true
            ],
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
            'sql' => "char(1) NOT NULL default ''"
        ],
        'blankOptionLabel' => [
            'inputType' => 'text',
            'default' => '-',
            'eval' => [
                'maxlength' => 128,
                'tl_class' => 'w50',
            ],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '-']
        ],
        'extensions' => [
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'extnd',
                'maxlength' => 255,
                'mandatory' => true,
                'tl_class' => 'long clr'
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
            'sql' => "binary(16) NULL"
        ],
        'useHomeDir' => [
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
            'sql' => "char(1) NOT NULL default ''"
        ],
        'imageWidth' => [
            'inputType' => 'text',
            'eval' => [
                'nospace' => true,
                'rgxp' => 'natural',
                'tl_class' => 'w50',
                'maxval' => \Config::get('imageWidth') > 0 ? \Config::get('imageWidth') : null
            ],
            'sql' => "int(10) unsigned NULL"
        ],
        'imageHeight' => [
            'inputType' => 'text',
            'eval' => [
                'nospace' => true,
                'rgxp' => 'natural',
                'tl_class' => 'w50',
                'maxval' => \Config::get('imageHeight') > 0 ? \Config::get('imageHeight') : null
            ],
            'sql' => "int(10) unsigned NULL"
        ],
        'csv' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr'
            ],
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];

array_insert($GLOBALS['TL_DCA']['tl_catalog_field']['fields'], 0, \Alnv\ContaoCatalogManagerBundle\Helper\OptionSourcePalette::getFields());