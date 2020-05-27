<?php

if (\Input::get('do')) {
    $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule( \Input::get('do'), [
        'limit' => 1
    ]);
    if ($objCatalog !== null) {
        if ($objCatalog->enableContentElements) {
            $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = $objCatalog->table;
        }
    }
}
if (\Input::get('do') == 'catalog-element') {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_catalog_element';
}

foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $strPalette => $strFields) {
    if ( in_array($strPalette, [ '__selector__', 'default' ])) {
        continue;
    }
    \Contao\CoreBundle\DataContainer\PaletteManipulator::create()
        ->addField('cmHide', 'type_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND )
        ->applyToPalette($strPalette, 'tl_content');
}

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['catalogmanager.hooks.element', 'onloadCallback'];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmHideOnDetailPage'] = [
    'sql' => "char(1) NOT NULL default ''"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['cmHide'] = [
    'inputType' => 'select',
    'eval' => [
        'multiple' => false,
        'tl_class' => 'w50',
        'includeBlankOption' => true
    ],
    'options' => ['autoitem', 'default'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['cmHide'],
    'exclude' => true,
    'sql' => "varchar(16) NOT NULL default ''"
];