<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\Controller;
use Contao\DataContainer;
use Contao\System;

class CatalogPalette
{

    public function getFieldOptions($strFieldId): array
    {

        $objField = CatalogFieldModel::findByPk($strFieldId);
        if (!$objField) {
            return [];
        }

        $objOptions = Options::getInstance($objField->fieldname . '.' . $objField->pid);
        $objOptions::setParameter($objField->row(), null);

        return $objOptions::getOptions();
    }

    public function getFieldsByCatalogId($strCatalogId, $strType): array
    {

        $arrReturn = [];
        $objCatalog = CatalogModel::findByPk($strCatalogId);
        $objFields = CatalogFieldModel::findByParent($strCatalogId);

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
            $arrReturn['published'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.published', Toolkit::getLabel('published'));
            $arrReturn['stop'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.stop', Toolkit::getLabel('stop'));
            $arrReturn['start'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.start', Toolkit::getLabel('start'));
        }

        return $this->extendWithDcaFields($objCatalog->table, $arrReturn);
    }

    public function getFields(DataContainer $objDataContainer): array
    {

        $arrReturn = [];
        $objCatalog = CatalogModel::findByPk($objDataContainer->activeRecord->pid);
        $objFields = CatalogFieldModel::findByParent($objDataContainer->activeRecord->pid);

        if (!$objFields || !$objCatalog) {
            return [];
        }

        while ($objFields->next()) {
            $arrReturn[$objFields->id] = $objFields->name;
        }

        if ($objCatalog->enableVisibility) {
            $arrReturn['published'] = Translation::getInstance()->translate($objCatalog->table . '.field.title.published', Toolkit::getLabel('published'));
        }

        return $arrReturn;
    }

    protected function extendWithDcaFields($strTable, $arrFields): array
    {

        Controller::loadDataContainer($strTable);
        System::loadLanguageFile($strTable);

        foreach (($GLOBALS['TL_DCA'][$strTable]['fields'] ?? []) as $strField => $arrField) {

            if (isset($arrReturn[$strField])) {
                continue;
            }

            if (!isset($arrField['inputType'])) {
                continue;
            }

            $arrFields[$strField] = $GLOBALS['TL_LANG'][$strTable][$strField][0] ?? $strField;
        }

        return $arrFields;
    }
}