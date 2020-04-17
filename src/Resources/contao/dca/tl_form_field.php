<?php

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'optionsSource';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'dbFilter';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'dbFilterType';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'includeBlankOption';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['optionsSource_options'] = 'options';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['dbFilter'] = 'dbFilterType';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['includeBlankOption'] = 'blankOptionLabel';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['dbFilterType_wizard'] = 'dbWizardFilterSettings';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['dbFilterType_expert'] = 'dbFilterColumn,dbFilterValue';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['optionsSource_dbOptions'] = \Alnv\ContaoCatalogManagerBundle\Helper\OptionSourcePalette::getPalette();
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['checkbox'] = str_replace('{options_legend},options', '{options_legend},optionsSource', $GLOBALS['TL_DCA']['tl_form_field']['palettes']['checkbox']);
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['select'] = str_replace('{options_legend},options', '{options_legend},optionsSource,includeBlankOption', $GLOBALS['TL_DCA']['tl_form_field']['palettes']['select']);
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['radio'] = str_replace('{options_legend},options', '{options_legend},optionsSource,includeBlankOption', $GLOBALS['TL_DCA']['tl_form_field']['palettes']['radio']);

array_insert($GLOBALS['TL_DCA']['tl_form_field']['fields'], 0, \Alnv\ContaoCatalogManagerBundle\Helper\OptionSourcePalette::getFields());

$GLOBALS['TL_DCA']['tl_form_field']['fields']['includeBlankOption'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
        'multiple' => false,
        'submitOnChange' => true
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['blankOptionLabel'] = [
    'inputType' => 'text',
    'default' => '-',
    'eval' => [
        'maxlength' => 128,
        'tl_class' => 'w50',
    ],
    'exclude' => true,
    'sql' => ['type' => 'string', 'length' => 128, 'default' => '-']
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['optionsSource'] = [
    'inputType' => 'radio',
    'default' => 'options',
    'eval' => [
        'maxlength' => 64,
        'tl_class' => 'clr',
        'submitOnChange' => true,
        'includeBlankOption' => true
    ],
    'options' => [ 'options', 'dbOptions' ],
    'reference' => $GLOBALS['TL_LANG']['tl_form_field']['reference']['optionsSource'],
    'exclude' => true,
    'sql' => ['type' => 'string', 'length' => 64, 'default' => 'options']
];