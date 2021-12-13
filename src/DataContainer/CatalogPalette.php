<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;

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

    public function getFieldsByCatalogId($strCatalogId, $strType) {

        $arrReturn = [];
        $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByPk($strCatalogId);
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByParent($strCatalogId);

        if (!$objFields || !$objCatalog) {
            return [];
        }

        if ($strType == 'palette') {
            $arrReturn['__FIELDSET__'] = 'FIELDSET';
        }

        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }

        if ($objCatalog->enableVisibility) {
            $arrReturn['published'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.published', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('published'));
            $arrReturn['stop'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.stop', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('stop'));
            $arrReturn['start'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.start', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('start'));
        }

        return $arrReturn;
    }

    public function getFields(\DataContainer $objDataContainer) {

        $arrReturn = [];
        $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByPk($objDataContainer->activeRecord->pid);
        $objFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findByParent($objDataContainer->activeRecord->pid);

        if (!$objFields || !$objCatalog) {
            return [];
        }

        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }

        if ($objCatalog->enableVisibility) {
            $arrReturn['published'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.published', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('published'));
        }

        return $arrReturn;
    }
}