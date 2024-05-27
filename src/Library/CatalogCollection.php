<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;

class CatalogCollection extends CatalogWizard
{

    protected array $arrCatalogs = [];

    protected array $arrTypes = [];

    public function __construct()
    {

        $objCatalogs = CatalogModel::findAll([
            'order' => 'sorting ASC'
        ]);

        if ($objCatalogs === null) {
            return null;
        }

        while ($objCatalogs->next()) {

            if (!$objCatalogs->table) {
                continue;
            }

            if (!isset($this->arrTypes[$objCatalogs->type])) {
                $this->arrTypes[$objCatalogs->type] = [];
            }

            $this->arrTypes[$objCatalogs->type][] = $objCatalogs->table;
            $this->arrCatalogs[$objCatalogs->table] = $this->parseCatalog($objCatalogs->row());
        }
    }


    public function getCatalogs($strType = ''): array
    {

        if (!$strType) {
            return $this->arrCatalogs;
        }

        $arrReturn = [];

        foreach ($this->arrCatalogs as $strTable => $arrCatalog) {
            if ($arrCatalog['type'] != $strType) {
                continue;
            }
            $arrReturn[$strTable] = $arrCatalog;
        }

        return $arrReturn;
    }
}