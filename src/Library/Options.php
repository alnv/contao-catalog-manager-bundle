<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;

class Options {

    protected static $arrField = [];
    protected static $arrInstances = [];
    protected static $arrDataContainer = null;

    public static function getInstance( $strInstanceId ) {

        if ( !array_key_exists( $strInstanceId, self::$arrInstances ) ) {

            self::$arrInstances[ $strInstanceId ] = new self;
        }

        return self::$arrInstances[ $strInstanceId ];
    }

    public static function getOptions($blnAsAssoc=false) {

        $arrReturn = [];
        switch ( static::$arrField['optionsSource'] ) {
            case 'options':
                $objOptions = CatalogOptionModel::findAll([
                    'column' => ['pid=?'],
                    'value' => [static::$arrField['id']],
                    'order' => 'sorting ASC'
                ]);
                if ( $objOptions === null ) {
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
                $objModel = new ModelWizard(static::$arrField['dbTable']);
                $objModel = $objModel->getModel();
                $arrModelOptions = [];
                array_insert($arrModelOptions, 0, self::setFilter());
                $objEntities = $objModel->findAll($arrModelOptions);
                if ( $objEntities === null ) {
                    return $arrReturn;
                }
                while ($objEntities->next()) {
                    $strValue = $objEntities->{static::$arrField['dbKey']};
                    $strLabel = static::getCleanLabel($objEntities->{static::$arrField['dbLabel']}, static::$arrField['dbLabel'], static::$arrField['dbTable']);
                    if ($blnAsAssoc) {
                        $arrReturn[] = [
                            'value' => $strValue,
                            'label' => $strLabel
                        ];
                        continue;
                    }
                    $arrReturn[$strValue] = self::getLabel($strValue, $strLabel);
                }
                return $arrReturn;
                break;

            default:
                return $arrReturn;
                break;
        }

        return $arrReturn;
    }

    protected static function getCleanLabel($strValue, $strField, $strTable) {
        if (!$strTable || !$strField) {
            return $strValue;
        }
        \System::loadLanguageFile($strTable);
        \Controller::loadDataContainer($strTable);
        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        return \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseCatalogValue($strValue, \Widget::getAttributesFromDca($arrField, $strField, $strValue, $strField, $strTable), [], true);
    }

    protected static function setFilter() {

        $arrOptions = [];
        switch (static::$arrField['dbFilterType']) {
            case 'wizard':
                \Controller::loadDataContainer(static::$arrField['dbTable']);
                $strTable = $GLOBALS['TL_DCA'][static::$arrField['dbTable']]['config']['_table'] ?: static::$arrField['dbTable'];
                $arrQueries = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::convertComboWizardToModelValues(static::$arrField['dbWizardFilterSettings'],$strTable);
                $arrOptions['column'] = $arrQueries['column'];
                $arrOptions['value'] = $arrQueries['value'];
                break;
            case 'expert':
                static::$arrField['dbFilterValue'] = \Controller::replaceInsertTags(static::$arrField['dbFilterValue']);
                $arrOptions['column'] = explode(';',\StringUtil::decodeEntities(static::$arrField['dbFilterColumn']));
                $arrOptions['value'] = explode(';',\StringUtil::decodeEntities(static::$arrField['dbFilterValue']));
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
            unset($arrOptions['value'] );
            unset($arrOptions['column']);
        }

        return $arrOptions;
    }

    public static function setParameter( $arrField, $objDataContainer = null ) {

        static::$arrField = $arrField;
        static::$arrDataContainer = $objDataContainer;
    }

    protected static function getLabel($strValue, $strFallbackLabel='') {

        $strFallbackLabel = \StringUtil::decodeEntities($strFallbackLabel);
        return \Controller::replaceInsertTags(\Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate( static::$arrField['dbTable'] . '.option.' . $strValue , $strFallbackLabel));
    }
}