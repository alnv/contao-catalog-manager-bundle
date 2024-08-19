<?php

use Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogField;
use Alnv\ContaoCatalogManagerBundle\Helper\OptionSourcePalette;
use Contao\ArrayUtil;
use Contao\Config;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;

$GLOBALS['TL_DCA']['tl_catalog_field'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_catalog',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,fieldname' => 'index'
            ]
        ],
        'onsubmit_callback' => [
            function (DataContainer $dataContainer) {
                (new CatalogField())->changeFieldType($dataContainer->activeRecord->type, $dataContainer);
            }
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'panelLayout' => 'filter;search,limit',
            'headerFields' => ['type', 'name', 'table'],
            'child_record_callback' => ['catalogmanager.datacontainer.catalogfield', 'listFields']
        ],
        'label' => [
            'fields' => ['name', 'fieldname', 'type'],
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'showInHeader' => true
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
        '__selector__' => ['type', 'optionsSource', 'includeBlankOption', 'dbFilterType', 'dbFilter', 'rte'],
        'default' => '{general_settings},name,type',
        'explanation' => '{general_settings},name,type,fieldname,text;{published_legend},published',
        'empty' => '{general_settings},name,type,fieldname,role,useAsAlias,{published_legend},published',
        'text' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,size;{published_legend},published',
        'date' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory;{published_legend},published',
        'color' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory;{published_legend},published',
        'textarea' => '{general_settings},name,type;{field_settings},fieldname,role,description,mandatory,rte;{published_legend},published',
        'select' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,submitOnChange,csv,size;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'radio' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,submitOnChange;{options_legend},optionsSource,includeBlankOption;{published_legend},published',
        'checkbox' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,multiple,csv;{options_legend},optionsSource;{published_legend},published',
        'checkboxWizard' => '{general_settings},name,type;{field_settings},fieldname,role,description,useAsAlias,mandatory,csv;{options_legend},optionsSource;{published_legend},published',
        'pagepicker' => '{general_settings},name,type;fieldname,role,mandatory,multiple,{published_legend},published',
        'upload' => '{general_settings},name,type;{field_settings},fieldname,role,description,mandatory,imageSize;{frontend_legend},extensions,imageWidth,imageHeight,uploadFolder,useHomeDir,doNotOverwrite;{published_legend},published',
        'listWizard' => '{general_settings},name,type;{field_settings},fieldname,role,description,mandatory;{published_legend},published',
        'customOptionWizard' => '{general_settings},name,type;{field_settings},fieldname,role,mandatory;{options_legend},optionsSource;{published_legend},published'
    ],
    'subpalettes' => [
        'rte' => 'rteType',
        'dbFilter' => 'dbFilterType',
        'dbFilterType_expert' => 'dbFilterColumn,dbFilterValue',
        'dbFilterType_wizard' => 'dbWizardFilterSettings',
        'includeBlankOption' => 'blankOptionLabel',
        'optionsSource_options' => 'optionsDcaWizard',
        'optionsSource_dbOptions' => OptionSourcePalette::getPalette(),
        'optionsSource_dbActiveOptions' => OptionSourcePalette::getActivePalette()
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
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
            'eval' => [
                'chosen' => true,
                'maxlength' => 32,
                'tl_class' => 'w50',
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options_callback' => ['catalogmanager.datacontainer.catalogfield', 'getFieldTypes'],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_field']['reference']['type'],
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
        'text' => [
            'inputType' => 'textarea',
            'eval' => [
                'rte' => 'tinyMCE',
                'mandatory' => true,
                'tl_class' => 'clr'
            ],
            'sql' => 'text NULL'
        ],
        'optionsSource' => [
            'inputType' => 'radio',
            'eval' => [
                'maxlength' => 64,
                'tl_class' => 'clr',
                'submitOnChange' => true,
                'includeBlankOption' => true
            ],
            'options' => ['options', 'dbOptions', 'dbActiveOptions'],
            'reference' => &$GLOBALS['TL_LANG']['tl_catalog_field']['reference']['optionsSource'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'optionsDcaWizard' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_catalog_option',
            'foreignField' => 'pid',
            'params' => [
                'dcaWizard' => Input::get('id')
            ],
            'eval' => [
                'showOperations' => true,
                'orderField' => 'sorting ASC',
                'operations' => ['edit', 'delete'],
                'fields' => ['id', 'label', 'value']
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
            'options_callback' => ['catalogmanager.datacontainer.catalogfield', 'getImageSizes'],
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
                'multiple' => false,
                'submitOnChange' => true
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'rteType' => [
            'inputType' => 'select',
            'default' => 'tinyMCE',
            'eval' => [
                'tl_class' => 'w50',
                'decodeEntities' => true
            ],
            'options' => ['tinyMCE', 'ace|html'],
            'sql' => "varchar(16) NOT NULL default ''"
        ],
        'published' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'doNotCopy' => true
            ],
            'filter' => true,
            'toggle' => true,
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
            'sql' => "varchar(255) NOT NULL default 'jpg,jpeg,gif,png,pdf,svg,doc,docx,xls,xlsx,ppt,pptx'"
        ],
        'uploadFolder' => [
            'inputType' => 'fileTree',
            'eval' => [
                'mandatory' => false,
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
                'maxval' => Config::get('imageWidth') > 0 ? Config::get('imageWidth') : null
            ],
            'sql' => "int(10) unsigned NULL"
        ],
        'imageHeight' => [
            'inputType' => 'text',
            'eval' => [
                'nospace' => true,
                'rgxp' => 'natural',
                'tl_class' => 'w50',
                'maxval' => Config::get('imageHeight') > 0 ? Config::get('imageHeight') : null
            ],
            'sql' => "int(10) unsigned NULL"
        ],
        'csv' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr'
            ],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'submitOnChange' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
            ],
            'filter' => true,
            'sql' => "char(1) NOT NULL default ''"
        ]
    ]
];

ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_catalog_field']['fields'], 0, OptionSourcePalette::getFields());