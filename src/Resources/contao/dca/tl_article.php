<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

foreach ( $GLOBALS['TL_DCA']['tl_article']['palettes'] as $strPalette => $strFields ) {

    if ( in_array( $strPalette, [ '__selector__' ] ) ) {

        continue;
    }

    PaletteManipulator::create()
        ->addField('cmHideOnDetailPage', 'title_legend', PaletteManipulator::POSITION_APPEND )
        ->applyToPalette( $strPalette, 'tl_article');
}

$GLOBALS['TL_DCA']['tl_article']['fields']['cmHideOnDetailPage'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50 m12'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];