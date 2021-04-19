<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;

class Options {

    protected static $arrField = [];
    protected static $arrInstances = [];
    protected static $strInstanceId = null;
    protected static $arrDataContainer = null;

    public static function getInstance($strInstanceId) {

        if (!$strInstanceId) {
            $strInstanceId = uniqid();
        }

        if (!array_key_exists($strInstanceId, static::$arrInstances)) {
            static::$strInstanceId = $strInstanceId;
            static::$arrInstances[$strInstanceId] = new static;
        }

        return static::$arrInstances[$strInstanceId];
    }

    protected static function getGetterId() {

        return (self::$arrField['fieldname']?self::$arrField['fieldname'] . '.':'') . (self::$arrField['id']?:static::$strInstanceId);
    }

    public static function getOptions($blnAsAssoc=false) {

        $arrTemps = [];
        $arrReturn = [];
        $strGetter = static::getGetterId();
        
        if (\Cache::has($strGetter)) {
            return \Cache::get($strGetter);
        }

        switch (self::$arrField['optionsSource']) {
            case 'options':
                $objOptions = CatalogOptionModel::findAll([
                    'column' => ['pid=?'],
                    'value' => [self::$arrField['id']],
                    'order' => 'sorting ASC'
                ]);
                if ($objOptions === null) {
                    return $arrReturn;
                }
                while ($objOptions->next()) {
                    $strLabel = self::getLabel($objOptions->value, $objOptions->label);
                    $strValue = $objOptions->value;
                    if ($blnAsAssoc) {
                        $arrReturn[] = [
                            'value' => $strValue,
                            'label' => $strLabel
                        ];
                        continue;
                    }
                    $arrReturn[$strValue] = $strLabel;
                }
                break;
            case 'dbOptions':
                $arrField = self::$arrField;
                $objEntities = self::getEntities();
                if ($objEntities === null) {
                    return $arrReturn;
                }
                while ($objEntities->next()) {
                    $varValues = self::getValue($objEntities->{$arrField['dbKey']}, $arrField['dbKey'], $arrField['dbTable']);
                    foreach ($varValues as $strValue) {
                        if (in_array($strValue, $arrTemps)) {
                            continue;
                        }
                        $arrTemps[] = $strValue;
                        $strLabel = self::getCleanLabel($objEntities->{$arrField['dbLabel']}, $arrField['dbLabel'], $arrField['dbTable']);
                        if ($blnAsAssoc) {
                            $arrReturn[] = [
                                'value' => $strValue,
                                'label' => self::getLabel($strValue, $strLabel)
                            ];
                            continue;
                        }
                        $arrReturn[$strValue] = self::getLabel($strValue, $strLabel);
                    }
                }
                \Cache::set($strGetter, $arrReturn);
                return $arrReturn;

            case 'dbActiveOptions':
                $arrField = self::$arrField;
                $objEntities = self::getEntities();
                if ($objEntities === null) {
                    return $arrReturn;
                }
                while ($objEntities->next()) {
                    $varValues = self::getValue($objEntities->{$arrField['dbKey']}, $arrField['dbKey'], $arrField['dbTable']);
                    foreach ($varValues as $strValue) {
                        if (in_array($strValue, $arrTemps)) {
                            continue;
                        }
                        $arrTemps[] = $strValue;
                        $strLabel = self::getCleanLabel($strValue, $arrField['dbKey'], $arrField['dbTable']);
                        if (!$strLabel) {
                            continue;
                        }
                        if ($blnAsAssoc) {
                            $arrReturn[] = [
                                'value' => $strValue,
                                'label' => self::getLabel($strValue, $strLabel)
                            ];
                            continue;
                        }
                        $arrReturn[$strValue] = self::getLabel($strValue, $strLabel);
                    }
                }

                \Cache::set($strGetter, $arrReturn);
                return $arrReturn;
        }

        \Cache::set($strGetter, $arrReturn);

        return $arrReturn;
    }

    protected static function getValue($strValue, $strField, $strTable) {

        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        if ($arrField['eval'] && isset($arrField['eval']['multiple']) && $arrField['eval']['multiple'] === true) {
            if (isset($arrField['eval']['csv']) && $arrField['eval']['csv']) {
                return explode($arrField['eval']['csv'], $strValue);
            }
        }

        return \StringUtil::deserialize($strValue, true);
    }

    protected static function getEntities() {

        $objModel = new ModelWizard(self::$arrField['dbTable']);
        $objModel = $objModel->getModel();
        $arrModelOptions = [];
        array_insert($arrModelOptions, 0, self::setFilter());
        if (self::$arrField['dbOrderField']) {
            $strTable = $GLOBALS['TL_DCA'][self::$arrField['dbTable']]['config']['_table'] ?: self::$arrField['dbTable'];
            $arrModelOptions['order'] = $strTable . '.' . self::$arrField['dbOrderField'] . ' ' . (self::$arrField['dbOrder'] ? strtoupper(self::$arrField['dbOrder']) : 'ASC');
        }
        return $objModel->findAll($arrModelOptions);
    }

    protected static function getCleanLabel($strValue, $strField, $strTable) {

        if (!$strTable || !$strField) {
            return $strValue;
        }

        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        return \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseCatalogValue($strValue, \Widget::getAttributesFromDca($arrField, $strField, $strValue, $strField, $strTable), [], true);
    }

    protected static function setFilter() {

        $arrOptions = [];
        switch (self::$arrField['dbFilterType']) {
            case 'wizard':
                $strTable = $GLOBALS['TL_DCA'][self::$arrField['dbTable']]['config']['_table'] ?: self::$arrField['dbTable'];
                $arrQueries = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::convertComboWizardToModelValues(self::$arrField['dbWizardFilterSettings'],$strTable);
                $arrOptions['column'] = $arrQueries['column'];
                $arrOptions['value'] = $arrQueries['value'];
                break;
            case 'expert':
                self::$arrField['dbFilterValue'] = \Controller::replaceInsertTags(self::$arrField['dbFilterValue']);
                $arrOptions['column'] = explode(';',\StringUtil::decodeEntities(self::$arrField['dbFilterColumn']));
                $arrOptions['value'] = explode(';',\StringUtil::decodeEntities(self::$arrField['dbFilterValue']));
                if ((is_array($arrOptions['value']) && !empty($arrOptions['value']))) {
                    $intIndex = -1;
                    $arrOptions['value'] = array_filter($arrOptions['value'], function ($strValue) use (&$intIndex, $arrOptions) {
                        $intIndex = $intIndex + 1;
                        if ($strValue === '' || $strValue === null) {
                            unset($arrOptions['column'][ $intIndex ]);
                            return false;
                        }
                        return true;
                    });
                    if (empty($arrOptions['value'])) {
                        unset($arrOptions['value']);
                        unset($arrOptions['column']);
                    }
                }
                break;
        }

        if (empty($arrOptions['value'])) {
            unset($arrOptions['value']);
            unset($arrOptions['column']);
        }

        return $arrOptions;
    }

    public static function setParameter($arrField, $objDataContainer=null) {

        self::$arrField = $arrField;
        self::$arrDataContainer = $objDataContainer;

        if (self::$arrField['dbTable']) {
            \System::loadLanguageFile(self::$arrField['dbTable']);
            \Controller::loadDataContainer(self::$arrField['dbTable']);
        }
    }

    protected static function getLabel($strValue, $strFallbackLabel='') {

        $strTable = self::$arrField['dbTable'] ?: 'option';
        $strFallbackLabel = \StringUtil::decodeEntities($strFallbackLabel);
        return \Controller::replaceInsertTags(\Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate(($strTable?$strTable.'.':'') . (self::$arrField['fieldname']?:self::$arrField['dbKey']) . '.' . $strValue, $strFallbackLabel));
    }
}