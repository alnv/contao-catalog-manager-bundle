<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class Options
{
    protected static array $arrField = [];

    protected static ?string $strInstanceId = null;

    protected static DataContainer|array|null $arrDataContainer = null;

    protected static array $arrInstances = [];

    public static function getInstance(?string $strInstanceId = null): static
    {
        if (empty($strInstanceId)) {
            $strInstanceId = uniqid('', true);
        }

        if (!array_key_exists($strInstanceId, static::$arrInstances)) {
            $instance = new static();
            static::$strInstanceId = $strInstanceId;
            static::$arrInstances[$strInstanceId] = $instance;
        }

        return static::$arrInstances[$strInstanceId];
    }

    protected static function getGetterId(): string
    {
        self::$arrField['id'] = self::$arrField['id'] ?? '';
        self::$arrField['fieldname'] = self::$arrField['fieldname'] ?? '';

        return (self::$arrField['fieldname'] ? self::$arrField['fieldname'] . '.' : '') . (self::$arrField['id'] ?: static::$strInstanceId);
    }

    public static function getOptions(bool $blnAsAssoc = false): array
    {
        $strGetter = static::getGetterId();

        if (Cache::has($strGetter)) {
            $arrReturn = Cache::get($strGetter);
        } else {
            $arrReturn = [];
            $optionsSource = self::$arrField['optionsSource'] ?? '';

            switch ($optionsSource) {
                case 'options':
                    $objOptions = CatalogOptionModel::findAll([
                        'column' => ['pid=?'],
                        'value' => [self::$arrField['id']],
                        'order' => 'sorting ASC'
                    ]);

                    if ($objOptions !== null) {
                        while ($objOptions->next()) {
                            $arrReturn[$objOptions->value] = self::getLabel($objOptions->value, $objOptions->label);
                        }
                    }
                    break;

                case 'dbOptions':
                case 'dbActiveOptions':
                    $arrField = self::$arrField;
                    $objEntities = self::getEntities();

                    if ($objEntities === null) {
                        break;
                    }

                    $arrTemps = [];
                    while ($objEntities->next()) {
                        $varValues = self::getValue($objEntities->{$arrField['dbKey']}, $arrField['dbKey'], $arrField['dbTable']);

                        foreach ($varValues as $strValue) {
                            $strValue = trim($strValue);

                            if (in_array($strValue, $arrTemps, true)) {
                                continue;
                            }
                            $arrTemps[] = $strValue;

                            if ($optionsSource === 'dbOptions') {
                                $strLabel = self::getCleanLabel($objEntities->{$arrField['dbLabel']}, $arrField['dbLabel'], $arrField['dbTable']);
                            } else {
                                $strLabel = self::getCleanLabel($strValue, $arrField['dbKey'], $arrField['dbTable']);
                                if (!$strLabel) {
                                    continue;
                                }
                            }

                            $arrReturn[$strValue] = self::getLabel($strValue, $strLabel);
                        }
                    }
                    break;
            }

            Cache::set($strGetter, $arrReturn);
        }

        if ($blnAsAssoc) {
            $arrAssocReturn = [];
            foreach ($arrReturn as $strValue => $strLabel) {
                $arrAssocReturn[] = [
                    'value' => $strValue,
                    'label' => $strLabel
                ];
            }
            return $arrAssocReturn;
        }

        return $arrReturn;
    }

    protected static function getValue($strValue, $strField, $strTable)
    {

        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        if (isset($arrField['eval']) && is_array($arrField['eval'])) {
            if (isset($arrField['eval']['csv']) && $arrField['eval']['csv']) {
                return explode($arrField['eval']['csv'], $strValue);
            }
        }

        return StringUtil::deserialize($strValue, true);
    }

    protected static function getEntities(): ?Collection
    {
        $strTable = self::$arrField['dbTable'] ?? '';
        if (empty($strTable)) {
            return null;
        }

        $objModel = new ModelWizard($strTable);
        $objModel = $objModel->getModel();
        if ($objModel === null) {
            return null;
        }

        $arrModelOptions = [];

        ArrayUtil::arrayInsert($arrModelOptions, 0, self::setFilter());

        if (!empty(self::$arrField['dbOrderField'])) {
            $strRealTable = $GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?? $strTable;
            $strDirection = !empty(self::$arrField['dbOrder']) ? strtoupper(self::$arrField['dbOrder']) : 'ASC';
            $arrModelOptions['order'] = sprintf('%s.%s %s', $strRealTable, self::$arrField['dbOrderField'], $strDirection);
        }

        return $objModel->findAll($arrModelOptions);
    }

    protected static function getCleanLabel($strValue, $strField, $strTable)
    {
        if (!$strTable || !$strField) {
            return $strValue;
        }

        $arrField = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];

        return Toolkit::parseCatalogValue($strValue, Widget::getAttributesFromDca($arrField, $strField, $strValue, $strField, $strTable), [], true);
    }

    protected static function setFilter(): array
    {

        $arrOptions = [];
        switch (self::$arrField['dbFilterType']) {
            case 'wizard':
                $strTable = $GLOBALS['TL_DCA'][self::$arrField['dbTable']]['config']['_table'] ?? self::$arrField['dbTable']; // isset($GLOBALS['TL_DCA'][self::$arrField['dbTable']]['config']['_table']) ? $GLOBALS['TL_DCA'][self::$arrField['dbTable']]['config']['_table'] : self::$arrField['dbTable'];
                $arrQueries = Toolkit::convertComboWizardToModelValues(self::$arrField['dbWizardFilterSettings'], $strTable);
                $arrOptions['column'] = $arrQueries['column'] ?? [];
                $arrOptions['value'] = $arrQueries['value'] ?? [];
                break;
            case 'expert':

                self::$arrField['dbFilterValue'] = Toolkit::replaceInsertTags(self::$arrField['dbFilterValue']);

                $arrOptions['column'] = explode(';', StringUtil::decodeEntities(self::$arrField['dbFilterColumn']));
                $arrOptions['value'] = explode(';', StringUtil::decodeEntities(self::$arrField['dbFilterValue']));

                if ((is_array($arrOptions['value']) && !empty($arrOptions['value']))) {
                    $intIndex = -1;
                    $arrOptions['value'] = array_filter($arrOptions['value'], function ($strValue) use (&$intIndex, $arrOptions) {
                        $intIndex = $intIndex + 1;
                        if ($strValue === '' || $strValue === null) {
                            unset($arrOptions['column'][$intIndex]);
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

    public static function setParameter($arrField, $objDataContainer = null): void
    {

        self::$arrField = $arrField;
        self::$arrDataContainer = $objDataContainer;

        if (self::$arrField['dbTable']) {
            System::loadLanguageFile(self::$arrField['dbTable']);
            Controller::loadDataContainer(self::$arrField['dbTable']);
        }
    }

    protected static function getLabel($strValue, $strFallbackLabel = ''): string
    {

        $strTable = self::$arrField['dbTable'] ?: 'option';
        $strFallbackLabel = StringUtil::decodeEntities($strFallbackLabel);

        return Toolkit::replaceInsertTags(Translation::getInstance()->translate(($strTable ? $strTable . '.' : '') . (self::$arrField['fieldname'] ?: self::$arrField['dbKey']) . '.' . $strValue, $strFallbackLabel));
    }
}