<?php

use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;

$GLOBALS['TL_DCA']['tl_page']['palettes']['filter'] = $GLOBALS['TL_DCA']['tl_page']['palettes']['regular'];

PaletteManipulator::create()->addField('cmRouting', 'routePriority')->applyToPalette('filter', 'tl_page');
PaletteManipulator::create()->addField('cmRoutePath', 'cmRouting')->applyToPalette('filter', 'tl_page');
PaletteManipulator::create()->removeField('routePath')->applyToPalette('filter', 'tl_page');

$GLOBALS['TL_DCA']['tl_page']['config']['ctable'][] = 'tl_page_filter';

$GLOBALS['TL_DCA']['tl_page']['fields']['cmRoutePath'] = [
    'input_field_callback' => function ($objDataContainer) {
        $arrActiveRecord = Toolkit::getActiveRecordAsArrayFromDc($objDataContainer);
        $strFragments = [$arrActiveRecord['alias']];
        foreach (Getters::getPageFiltersByPageId($objDataContainer->id) as $objFilterPage) {
            $strFragments[] = '{' . $objFilterPage->getAlias() . '}';
        }
        $strFragments[] = '{auto_item}';
        return '<div class="w50 widget"><h3>' . ($GLOBALS['TL_LANG']['tl_page']['routePath'][0] ?? '') . '</h3><p class="info">' . implode('/', $strFragments) . '</p><p class="tl_help tl_tip">' . ($GLOBALS['TL_LANG']['tl_page']['routePath'][1] ?? '') . '</p></div>';
    },
    'exclude' => true
];

$GLOBALS['TL_DCA']['tl_page']['fields']['cmRouting'] = [
    'inputType' => 'dcaWizard',
    'foreignTable' => 'tl_page_filter',
    'foreignField' => 'pid',
    'params' => [
        'dcaWizard' => Input::get('id')
    ],
    'eval' => [
        'tl_class' => 'clr',
        'showOperations' => true,
        'orderField' => 'sorting ASC',
        'operations' => ['show'],
        'global_operations' => ['new'],
        'editButtonLabel' => ($GLOBALS['TL_LANG']['tl_page']['editButtonLabel'] ?? ''),
        'emptyLabel' => ($GLOBALS['TL_LANG']['tl_page']['emptyLabel'] ?? ''),
        'fields' => ['type', 'table', 'column', 'alias']
    ],
    'exclude' => true
];