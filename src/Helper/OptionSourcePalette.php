<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

class OptionSourcePalette {

    public static function getPalette() {

        return 'dbTable,dbKey,dbLabel';
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
            ]
        ];
    }
}