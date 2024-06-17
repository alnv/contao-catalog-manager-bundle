<?php

use Contao\System;
use Contao\Controller;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;

$GLOBALS['TL_DCA']['tl_user_group']['config']['onload_callback'][] =  function () {
    $objCatalogs = CatalogModel::findAll();

    if (!$objCatalogs) {
        return;
    }

    while ($objCatalogs->next()) {

        if (!$objCatalogs->table) {
            continue;
        }

        System::loadLanguageFile($objCatalogs->table);
        Controller::loadDataContainer($objCatalogs->table);
    }
};