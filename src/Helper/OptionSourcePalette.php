<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

class OptionSourcePalette {

    public static function getPalette() {

        return 'dbTable,dbKey,dbLabel,dbFilterType';
    }

    public static function getFields() {

        return [
            'dbTable' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbTable'],
                'inputType' => 'select',
                'eval' => [
                    'chosen' => true,
                    'maxlength' => 128,
                    'tl_class' => 'w50',
                    'mandatory' => true,
                    'submitOnChange' => true,
                    'includeBlankOption' => true
                ],
                'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getTables' ],
                'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
            ],
            'dbKey' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbKey'],
                'inputType' => 'select',
                'default' => 'id',
                'eval' => [
                    'chosen' => true,
                    'maxlength' => 128,
                    'mandatory' => true,
                    'tl_class' => 'w50 clr'
                ],
                'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getDbFields' ],
                'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
            ],
            'dbLabel' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbLabel'],
                'inputType' => 'select',
                'eval' => [
                    'chosen' => true,
                    'maxlength' => 128,
                    'mandatory' => true,
                    'tl_class' => 'w50',
                    'includeBlankOption' => true
                ],
                'options_callback' => [ 'catalogmanager.datacontainer.catalog', 'getDbFields' ],
                'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
            ],
            'dbFilterType' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterType'],
                'inputType' => 'radio',
                'default' => 'wizard',
                'eval' => [
                    'tl_class' => 'clr',
                    'submitOnChange' => true
                ],
                'options' => [ 'wizard', 'expert' ],
                'reference' => &$GLOBALS['TL_LANG']['MSC']['reference']['optionSourceDbFilterType'],
                'exclude' => true,
                'sql' => "varchar(12) NOT NULL default 'wizard'"
            ],
            'dbFilterColumn' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterColumn'],
                'inputType' => 'textarea',
                'eval' => [
                    'tl_class' => 'clr'
                ],
                'exclude' => true,
                'sql' => "mediumtext NULL"
            ],
            'dbFilterValue' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbFilterValue'],
                'inputType' => 'textarea',
                'eval' => [
                    'tl_class' => 'clr'
                ],
                'exclude' => true,
                'sql' => "mediumtext NULL"
            ],
            'dbWizardFilterSettings' => [
                'label' => $GLOBALS['TL_LANG']['MSC']['optionSourceDbWizardFilterSettings'],
                'inputType' => 'comboWizard',
                'eval' => [
                    'tl_class' => 'clr',
                    'mandatory' => false,
                    'options2_callback' => ['catalogmanager.datacontainer.catalog', 'getOperators'],
                    'enableField' => true,
                    'enableGroup' => true
                ],
                'options_callback' => ['catalogmanager.datacontainer.catalog', 'getDbFields'],
                'sql' => ['type' => 'blob', 'notnull' => false ]
            ]
        ];
    }
}