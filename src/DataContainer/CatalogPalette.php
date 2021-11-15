<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

class CatalogPalette {

    public function getFieldsByCatalogId($strCatalogId) {

        $arrReturn = [];
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByParent($strCatalogId);

        if (!$objFields) {
            return [];
        }

        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }

        $arrReturn['__FIELDSET__'] = 'FIELDSET';

        return $arrReturn;
    }

    public function getCssClasses() {

        return ['w50', 'clr', 'wizard', 'long', 'cbx', 'm12', 'cbx m12'];
    }

    public function getFields(\DataContainer $objDataContainer) {

        $arrReturn = [];
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByParent($objDataContainer->activeRecord->pid);
        if (!$objFields) {
            return [];
        }
        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }
        return $arrReturn;
    }
}