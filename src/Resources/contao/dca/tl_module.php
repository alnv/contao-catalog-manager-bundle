<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmFilter';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmMaster';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilter'] = 'cmColumn,cmValue';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmMaster'] = 'cmMasterPage,cmMasterModule';

$GLOBALS['TL_DCA']['tl_module']['palettes']['listing'] = '{title_legend},name,headline,type;{listing_settings},cmTable,cmTemplate,cmMaster,cmFilter,cmPagination,cmLimit,cmOffset,cmGroupBy,cmGroupByHl;{radius_search_legend},cmRadiusSearch;{template_legend:hide},customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['master'] = '{title_legend},name,headline,type;{master_settings},cmTable,cmTemplate;{template_legend:hide},customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['cmTable'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmTable'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmTemplate'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmPagination'],
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr m12'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmLimit'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmLimit'],
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmOffset'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmOffset'],
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmGroupBy'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmGroupBy'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmGroupByHl'],
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
$GLOBALS['TL_DCA']['tl_module']['fields']['cmFilter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmFilter'],
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr m12',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmColumn'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmColumn'],
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmValue'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmValue'],
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmMaster'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmMaster'],
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr m12',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['cmMasterModule'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmMasterModule'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmMasterPage'],
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
    'label' => &$GLOBALS['TL_LANG']['tl_module']['cmRadiusSearch'],
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];