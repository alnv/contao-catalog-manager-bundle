<?php

\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addField('cmHide', 'author', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
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