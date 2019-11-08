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