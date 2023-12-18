<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;

class CatalogOption
{

    public function getFieldLabels(): array
    {
        $objFields = CatalogFieldModel::findAll();

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