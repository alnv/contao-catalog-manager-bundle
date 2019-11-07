<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmFilter';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmMaster';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmFilterType';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilter'] = 'cmFilterType';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmMaster'] = 'cmMasterPage,cmMasterModule';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilterType_expert'] = 'cmColumn,cmValue';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilterType_wizard'] = 'cmWizardFilterSettings';

$GLOBALS['TL_DCA']['tl_module']['palettes']['listing'] = '{title_legend},name,headline,type;{listing_settings},cmTable,cmTemplate,cmMaster,cmFilter,cmPagination,cmLimit,cmOffset,cmGroupBy,cmGroupByHl,cmOrder;{radius_search_settings},cmRadiusSearch;{template_legend:hide},customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['master'] = '{title_legend},name,headline,type;{master_settings},cmTable,cmTemplate;{template_legend:hide},customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['cmTable'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'mandatory' => true,
        'submitOnChange' => true,
        'includeBlankOption'=> true,
    ],
    'options_callback' => [ 'catalogmanager.datacontainer.module', 'getTables' ],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmTemplate'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 255,
        'tl_class' => 'w50',
        'mandatory' => true
    ],
    'options_callback' => [ 'catalogmanager.datacontainer.module', 'getListTemplates' ],
    'exclude' => true,
    'sql' => "varchar(255) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmPagination'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmLimit'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmOffset'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmGroupBy'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 64,
        'tl_class' => 'w50',
        'includeBlankOption'=> true
    ],
    'options_callback' => [ 'catalogmanager.datacontainer.module', 'getFields' ],
    'exclude' => true,
    'sql' => "varchar(64) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmGroupByHl'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 2,
        'tl_class' => 'w50',
        'includeBlankOption'=> true
    ],
    'options' => [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
    'exclude' => true,
    'sql' => "varchar(2) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmOrder'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'w50',
        'mandatory' => true,
        'options2_callback' => [ 'catalogmanager.datacontainer.module', 'getOrderByStatements' ]
    ],
    'options_callback' => [ 'catalogmanager.datacontainer.module', 'getFields' ],
    'sql' => ['type' => 'blob', 'notnull' => false ]
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmFilter'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmFilterType'] = [
    'inputType' => 'radio',
    'default' => 'wizard',
    'eval' => [
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'options' => [ 'wizard', 'expert' ],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['reference']['cmFilterType'],
    'exclude' => true,
    'sql' => "varchar(12) NOT NULL default 'wizard'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmWizardFilterSettings'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'long',
        'mandatory' => true,
        'options2_callback' => [ 'catalogmanager.datacontainer.module', 'getOperators' ],
        'enableField' => true,
        'enableGroup' => true
    ],
    'options_callback' => [ 'catalogmanager.datacontainer.module', 'getFields' ],
    'sql' => ['type' => 'blob', 'notnull' => false ]
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmColumn'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmValue'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmMaster'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmMasterModule'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'tl_class' => 'w50',
        'includeBlankOption'=> true
    ],
    'foreignKey' => 'tl_module.name',
    'relation' => [
        'load' => 'lazy',
        'type' => 'hasOne'
    ],
    'exclude' => true,
    'sql' => "int(10) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmMasterPage'] = [
    'inputType' => 'pageTree',
    'eval' => [
        'tl_class' => 'w50 clr',
        'mandatory' => true
    ],
    'foreignKey' => 'tl_page.title',
    'relation' => [
        'load' => 'lazy',
        'type' => 'hasOne'
    ],
    'exclude' => true,
    'sql' => "int(10) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmRadiusSearch'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];