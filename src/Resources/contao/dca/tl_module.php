<?php

use Contao\Controller;
use Contao\DataContainer;
use Contao\Database;
use Alnv\ContaoCatalogManagerBundle\DataContainer\FrontendManager;

$GLOBALS['TL_DCA']['tl_module']['fields']['id']['search'] = true;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmFilter';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmMaster';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cmFilterType';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilter'] = 'cmFilterType';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmMaster'] = 'cmMasterPage,cmMasterModule';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilterType_expert'] = 'cmColumn,cmValue';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cmFilterType_wizard'] = 'cmWizardFilterSettings';

$GLOBALS['TL_DCA']['tl_module']['palettes']['listing-table'] = '{title_legend},name,headline,type;{listing_settings},cmTable,cmMaster,cmFilter,cmPagination,cmLimit,cmOffset,cmGroupBy,cmGroupByHl,cmOrder;{radius_search_settings},cmRadiusSearch;{performance_settings:hide},cmIgnoreFieldsFromParsing;{template_legend:hide},cmTemplate,customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['master'] = '{title_legend},name,headline,type;{master_settings},cmTable,cmFilter,cmIgnoreVisibility;{template_legend:hide},cmTemplate,customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['listing-map'] = '{title_legend},name,headline,type;{listing_settings},cmTable,cmTemplate,cmMaster,cmFilter,cmIgnoreVisibility,cmPagination,cmLimit,cmOffset,cmOrder,cmInfoContent;{radius_search_settings},cmRadiusSearch;{performance_settings:hide},cmIgnoreFieldsFromParsing;{template_legend:hide},customTpl;{protected_legend:hide:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = function (DataContainer $objDataContainer) {
    $objActiveRecord = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE id=?')->limit(1)->execute($objDataContainer->id);
    if ($objActiveRecord->type && in_array($objActiveRecord->type, ['listing-table', 'listing-map'])) {
        $GLOBALS['TL_DCA']['tl_module']['fields']['customTpl']['options_callback'] = function (DataContainer $objDataContainer) {
            $strSuffix = '';
            if ($objDataContainer->activeRecord->type == 'listing-map') {
                $strSuffix = 'map';
            }
            if ($objDataContainer->activeRecord->type == 'listing-table') {
                $strSuffix = 'table';
            }
            return Controller::getTemplateGroup('mod_listing_' . $strSuffix);
        };
    }
};

$GLOBALS['TL_DCA']['tl_module']['fields']['cmTable'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 128,
        'tl_class' => 'w50',
        'mandatory' => true,
        'submitOnChange' => true,
        'includeBlankOption' => true,
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getTables'],
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
    'options_callback' => ['catalogmanager.datacontainer.module', 'getListTemplates'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmPagination'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmIgnoreVisibility'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmLimit'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmOffset'] = [
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50'
    ],
    'sql' => "smallint(5) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmGroupBy'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 64,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmGroupByHl'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'maxlength' => 2,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    'sql' => "varchar(2) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmOrder'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'w50',
        'mandatory' => false,
        'decodeEntities' => true,
        'options2_callback' => ['catalogmanager.datacontainer.module', 'getOrderByStatements']
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => ['type' => 'blob', 'notnull' => false]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFilter'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFilterType'] = [
    'inputType' => 'radio',
    'default' => 'wizard',
    'eval' => [
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'options' => ['wizard', 'expert'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['reference']['cmFilterType'],
    'sql' => "varchar(12) NOT NULL default 'wizard'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmWizardFilterSettings'] = [
    'inputType' => 'comboWizard',
    'eval' => [
        'tl_class' => 'long',
        'mandatory' => true,
        'allowHtml' => true,
        'options2_callback' => ['catalogmanager.datacontainer.module', 'getOperators'],
        'enableField' => true,
        'enableGroup' => true
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => ['type' => 'blob', 'notnull' => false]
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmColumn'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'sql' => "mediumtext NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmValue'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr'
    ],
    'sql' => "mediumtext NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmInfoContent'] = [
    'inputType' => 'textarea',
    'eval' => [
        'tl_class' => 'clr',
        'allowHtml' => true
    ],
    'sql' => "mediumtext NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmMaster'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr',
        'submitOnChange' => true
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmMasterModule'] = [
    'inputType' => 'select',
    'eval' => [
        'chosen' => true,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'foreignKey' => 'tl_module.name',
    'relation' => [
        'load' => 'lazy',
        'type' => 'hasOne'
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmMasterPage'] = [
    'inputType' => 'pageTree',
    'eval' => [
        'tl_class' => 'w50 clr'
    ],
    'foreignKey' => 'tl_page.title',
    'relation' => [
        'load' => 'lazy',
        'type' => 'hasOne'
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmRadiusSearch'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'clr'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmIgnoreFieldsFromParsing'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => true,
        'tl_class' => 'clr'
    ],
    'options_callback' => ['catalogmanager.datacontainer.module', 'getFields'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = [FrontendManager::class, 'onLoad'];
$GLOBALS['TL_DCA']['tl_module']['config']['onsubmit_callback'][] = [FrontendManager::class, 'onSubmit'];

$GLOBALS['TL_DCA']['tl_module']['palettes']['catalog-frontend-manager'] = '{title_legend},name,headline,type;'
    . '{cm_fm_table_legend},cmTable;'
    . '{cm_fm_fields_legend},cmFmListFields,cmFmFormFields,cmFmReadonlyFields,cmFmFilterFields;'
    . '{cm_fm_list_legend},cmFmSortable,cmOrder,cmLimit;'
    . '{cm_fm_operations_legend},cmFmOperations,cmFmToggleField,cmFmToggleReverse;'
    . '{cm_fm_permission_legend},cmFmOwnerField,cmFilter,cmFmInternalValues;'
    . '{cm_fm_upload_legend:hide},cmFmUseHomeDir;'
    . '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmListFields'] = [
    'inputType' => 'checkboxWizard',
    'options_callback' => [FrontendManager::class, 'getListFields'],
    'save_callback' => [[FrontendManager::class, 'saveListFields']],
    'eval' => ['multiple' => true, 'tl_class' => 'w50 clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmFormFields'] = [
    'inputType' => 'checkbox',
    'options_callback' => [FrontendManager::class, 'getFormFields'],
    'save_callback' => [[FrontendManager::class, 'saveFormFields']],
    'eval' => ['multiple' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmReadonlyFields'] = [
    'inputType' => 'checkbox',
    'options_callback' => [FrontendManager::class, 'getFormFields'],
    'save_callback' => [[FrontendManager::class, 'saveFormFields']],
    'eval' => ['multiple' => true, 'tl_class' => 'w50 clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmFilterFields'] = [
    'inputType' => 'checkbox',
    'options_callback' => [FrontendManager::class, 'getFilterFields'],
    'save_callback' => [[FrontendManager::class, 'saveFilterFields']],
    'eval' => ['multiple' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmSortable'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12 clr'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmOperations'] = [
    'inputType' => 'checkbox',
    'options_callback' => [FrontendManager::class, 'getOperations'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['cmFmOperationsReference'],
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmToggleField'] = [
    'inputType' => 'select',
    'options_callback' => [FrontendManager::class, 'getToggleFields'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmToggleReverse'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmOwnerField'] = [
    'inputType' => 'select',
    'options_callback' => [FrontendManager::class, 'getOwnerFields'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmInternalValues'] = [
    'inputType' => 'keyValueWizard',
    'save_callback' => [[FrontendManager::class, 'saveInternalValues']],
    'eval' => ['tl_class' => 'clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cmFmUseHomeDir'] = [
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];
