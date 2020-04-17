<?php

foreach ( $GLOBALS['TL_DCA']['tl_article']['palettes'] as $strPalette => $strFields ) {
    if ( in_array( $strPalette, [ '__selector__' ] ) ) {
        continue;
    }
    \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
        ->addField('cmHideOnDetailPage', 'title_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
        ->applyToPalette($strPalette,'tl_article');
}

\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addField('cmHideOnDetailPage', 'alias', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default','tl_article');
$GLOBALS['TL_DCA']['tl_article']['palettes']['default'] = str_replace('author;', 'author;{catalog_legend},cmContentElement,cmContentElementPosition;', $GLOBALS['TL_DCA']['tl_article']['palettes']['default']);

$GLOBALS['TL_DCA']['tl_article']['fields']['cmContentElement'] = [
    'inputType' => 'select',
    'eval' => [
        'includeBlankOption' => true,
        'multiple' => false,
        'tl_class' => 'w50'
    ],
    'exclude' => true,
    'options_callback' => ['\Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogElement', 'getArticleElements'],
    'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
];
$GLOBALS['TL_DCA']['tl_article']['fields']['cmContentElementPosition'] = [
    'inputType' => 'select',
    'default' => 'before',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50'
    ],
    'options' => ['before', 'after'],
    'reference' => &$GLOBALS['TL_LANG']['tl_article']['reference']['cmContentElementPosition'],
    'exclude' => true,
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'before']
];
$GLOBALS['TL_DCA']['tl_article']['fields']['cmHideOnDetailPage'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50 m12'
    ],
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''"
];