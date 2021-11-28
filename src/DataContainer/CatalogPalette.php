<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Library\Options;

class CatalogPalette {

    public function getFieldOptions($strFieldId) {

        $objField = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByPk($strFieldId);
        if (!$objField) {
            return [];
        }

        $objOptions = Options::getInstance($objField->fieldname . '.' . $objField->pid);
        $objOptions::setParameter($objField->row(), null);

        return $objOptions::getOptions();
    }

    public function getFieldsByCatalogId($strCatalogId) {

        $arrReturn = [];
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByParent($strCatalogId);
        if (!$objFields) {
            return [];
        }

        $arrReturn['__FIELDSET__'] = 'FIELDSET';

        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }

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