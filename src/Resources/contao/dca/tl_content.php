<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['component'] = '{type_legend},type;{include_legend},module;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['listview'] = '{type_legend},type,headline;{listing_settings},cmTable,cmMaster,cmFilter,cmPagination,cmLimit,cmOffset,cmOrder;{radius_search_settings},cmRadiusSearch;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

if (\Input::get('do')) {
    $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule((\Input::get('sourceTable')?:\Input::get('do')), [
        'limit' => 1
    ]);
    if ($objCatalog !== null) {
        if ($objCatalog->enableContentElements) {
            $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = $objCatalog->table;
        }
    }
}
if (\Input::get('do') == 'catalog-element') {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_catalog_element';
}

foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $strPalette => $strFields) {
    if ( in_array($strPalette, [ '__selector__', 'default' ])) {
        continue;
    }
    \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
        ->addField('cmHide', 'type_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND )
        ->applyToPalette($strPalette, 'tl_content');
}

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['catalogmanager.hooks.element', 'onloadCallback'];

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'cmFilter';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'cmMaster';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'cmFilterType';

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['cmFilter'] = 'cmFilterType';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['cmMaster'] = 'cmMasterPage';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['cmFilterType_expert'] = 'cmColumn,cmValue';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['cmFilterType_wizard'] = 'cmWizardFilterSettings';

$GLOBALS['TL_DCA']['tl_content']['fields']['cmHideOnDetailPage'] = ['sql' => "char(1) NOT NULL default ''"]; // can be delete
$GLOBALS['TL_DCA']['tl_content']['fields']['cmHide'] = [
    'inputType' => 'select',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options' => ['autoitem', 'default'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['cmHide'],
    'exclude' => true,
    'sql' => "varchar(16) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['cmTable'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'mandatory' => true,
        'submitOnChange' => true,
        'includeBlankOption'=> true,
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getTables'],
    'exclude' => true,
    'sql' => "varchar(128) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmMaster'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmMasterPage'] = [
    'inputType' => 'pageTree',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'foreignKey' => 'tl_page.title',
    'relation' => [
        'load' => 'lazy',
        'type' => 'hasOne'
    ],
    'exclude' => true,
    'sql' => "int(10) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmOrder'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'w50',
        'mandatory' => false,
        'options2_callback' => ['catalogmanager.datacontainer.module', 'getOrderByStatements']
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => ['type' => 'blob', 'notnull' => false ]
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmFilter'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmFilterType'] = [
    'inputType' => 'radio',
    'default' => 'wizard',
    'eval' => [
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'options' => ['wizard', 'expert'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['cmFilterType'],
    'exclude' => true,
    'sql' => "varchar(12) NOT NULL default 'wizard'"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmWizardFilterSettings'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'long',
        'mandatory' => true,
        'options2_callback' => ['catalogmanager.datacontainer.module', 'getOperators'],
        'enableField' => true,
        'enableGroup' => true
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => ['type' => 'blob', 'notnull' => false ]
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmColumn'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmValue'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "mediumtext NULL"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmPagination'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmLimit'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmOffset'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50'
    ],
    'exclude' => true,
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmRadiusSearch'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];