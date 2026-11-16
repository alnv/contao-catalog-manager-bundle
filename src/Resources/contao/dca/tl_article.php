<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addField('cmHide', 'author')
    ->applyToPalette('default', 'tl_article');

$GLOBALS['TL_DCA']['tl_article']['fields']['cmHideOnDetailPage'] = [
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['cmHide'] = [
    'inputType' => 'select',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options' => ['autoitem', 'default'],
    'reference' => &$GLOBALS['TL_LANG']['tl_article']['reference']['cmHide'],
    'exclude' => true,
    'sql' => "varchar(16) NOT NULL default ''"
];