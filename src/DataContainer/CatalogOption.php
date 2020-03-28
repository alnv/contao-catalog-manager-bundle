<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

class CatalogOption {

    public function getFieldLabels() {
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findAll();
        if ($objFields === null) {
            return [];
        }
        $arrOptions = [];
        while ($objFields->next()) {
            $arrOptions[$objFields->id] = $objFields->name;
        }
        return $arrOptions;
    }
}