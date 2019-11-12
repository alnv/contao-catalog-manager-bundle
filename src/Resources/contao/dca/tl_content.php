<?php

if ( \Input::get('do') ) {

    $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule( \Input::get('do'), [
        'limit' => 1
    ]);

    if ( $objCatalog !== null ) {

        if ( $objCatalog->enableContentElements ) {

            $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = $objCatalog->table;
        }
    }
}

use Contao\CoreBundle\DataContainer\PaletteManipulator;

foreach ( $GLOBALS['TL_DCA']['tl_content']['palettes'] as $strPalette => $strFields ) {

    if ( in_array( $strPalette, [ '__selector__', 'default' ] ) ) {

        continue;
    }

    PaletteManipulator::create()
        ->addField('cmHideOnDetailPage', 'type_legend', PaletteManipulator::POSITION_APPEND )
        ->applyToPalette( $strPalette, 'tl_content');
}

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [ 'catalogmanager.hooks.element', 'onloadCallback' ];

$GLOBALS['TL_DCA']['tl_content']['fields']['cmHideOnDetailPage'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50 m12'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];