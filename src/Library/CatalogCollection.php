<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;

class CatalogCollection extends CatalogWizard
{
    protected static ?array $runtimeCache = null;

    protected array $arrCatalogs = [];

    protected array $arrTypes = [];

    public function __construct()
    {
        if (self::$runtimeCache !== null) {
            $this->arrCatalogs = self::$runtimeCache['catalogs'];
            $this->arrTypes    = self::$runtimeCache['types'];
            return;
        }

        $objCatalogs = CatalogModel::findAll([
            'order' => '`sorting` ASC'
        ]);

        if ($objCatalogs === null) {
            return;
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

        self::$runtimeCache = [
            'catalogs' => $this->arrCatalogs,
            'types'    => $this->arrTypes
        ];
    }

    public function getCatalogs($strType = ''): array
    {
        if (!$strType) {
            return $this->arrCatalogs;
        }

        if (!isset($this->arrTypes[$strType])) {
            return [];
        }

        $arrReturn = [];
        foreach ($this->arrTypes[$strType] as $strTable) {
            if (isset($this->arrCatalogs[$strTable])) {
                $arrReturn[$strTable] = $this->arrCatalogs[$strTable];
            }
        }

        return $arrReturn;
    }
}