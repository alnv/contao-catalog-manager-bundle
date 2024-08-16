<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Mode;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Database;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;

class ActiveInsertTag
{

    public function replace($strFragment)
    {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && strtoupper($arrFragments[0]) == 'ACTIVE' && isset($arrFragments[1])) {

            global $objPage;

            $strMode = null;
            $blnUseCsv = false;
            $strDefault = null;
            $blnUseDefault = false;
            $varValue = Toolkit::getValueFromUrl(Toolkit::getFilterValue($arrFragments[1]));

            $arrActiveOptions = [
                'touch' => false,
                'dateMethod' => 'dayBegin',
                'dateFormat' => $objPage->dateFormat
            ];

            if (isset($arrFragments[2]) && \strpos($arrFragments[2], '?') !== false) {
                $arrParams = Toolkit::parseParametersFromString($arrFragments[2]);
                foreach ($arrParams as $strParam) {
                    list($strKey, $strOption) = explode('=', $strParam);
                    switch ($strKey) {
                        case 'default':
                            $blnUseDefault = true;
                            $strDefault = $strOption;
                            break;
                        case 'mode':
                            $strMode = $strOption; // BE || FE
                            break;
                        case 'dateMethod':
                            $arrActiveOptions['dateMethod'] = $strOption;
                            break;
                        case 'dateFormat':
                            $arrActiveOptions['dateFormat'] = $strOption;
                            break;
                        case 'touch':
                            $arrActiveOptions['touch'] = (bool)$strOption;
                            break;
                        case 'source':
                            $arrOptions = explode(':', $strOption);
                            $strTable = $arrOptions[0] ?? '';
                            $strColumn = $arrOptions[1] ?? '';
                            $varValue = $this->getIdsByTableAndColumn($strTable, $strColumn, $varValue);
                            break;
                        case 'csv':
                            if ($varValue !== '') {
                                $blnUseCsv = true;
                            }
                            break;
                    }
                }
            }

            if ($blnUseDefault && ($varValue == '' || $varValue == null)) {
                $varValue = $strDefault;
            }

            if ($blnUseCsv && is_string($varValue)) {
                $varValue = \serialize(\explode(',', $varValue));
            }

            $varValue = StringUtil::deserialize($varValue);
            if (\is_array($varValue)) {
                foreach ($varValue as $intIndex => $strValue) {
                    $varValue[$intIndex] = $this->parseValue($strValue, $arrActiveOptions);
                }
            } else {
                $varValue = $this->parseValue($varValue, $arrActiveOptions);
            }

            if (isset($GLOBALS['TL_HOOKS']['replaceActiveInserttag']) && is_array($GLOBALS['TL_HOOKS']['replaceActiveInserttag'])) {
                foreach ($GLOBALS['TL_HOOKS']['replaceActiveInserttag'] as $arrCallback) {
                    $varValue = System::importStatic($arrCallback[0])->{$arrCallback[1]}($varValue, $arrFragments);
                }
            }

            if ($strMode) {
                if ($strMode == 'FE' && Mode::get() != $strMode) {
                    return '';
                }
                if ($strMode == 'BE' && Mode::get() != $strMode) {
                    return '';
                }
            }

            if (\is_array($varValue)) {
                return \serialize($varValue);
            }

            return $varValue;
        }

        return false;
    }

    protected function parseValue($strValue, $arrOptions = []): string
    {

        $strDateMethod = $arrOptions['dateMethod'] ?? 'dayBegin';
        $strDateFormat = $arrOptions['dateFormat'] ?? 'date';
        $blnTouch = $arrOptions['touch'] ?? false;

        if ($strValue === '') {
            return $strValue;
        }

        if (Validator::isDate($strValue)) {
            $strValue = (new Date($strValue, $strDateFormat))->{$strDateMethod};
        }

        if (Validator::isDatim($strValue)) {
            $strValue = (new Date($strValue, $strDateFormat))->{$strDateMethod};
        }

        if ($blnTouch) {
            $strValue = '[[:<:]]' . $strValue . '[[:>:]]';
        }

        return $strValue;
    }

    protected function getIdsByTableAndColumn($strTable, $strColumn, $varValue): string
    {

        if (!$varValue) {
            return '';
        }

        if (!Database::getInstance()->tableExists($strTable)) {
            return '';
        }

        if (!Database::getInstance()->fieldExists($strColumn, $strTable)) {
            return '';
        }

        $arrIds = [];
        $arrValues = StringUtil::deserialize($varValue, true);

        foreach ($arrValues as $strValue) {
            $strTable = $GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?? $strTable;
            $objModel = new ModelWizard($strTable);
            $objModel = $objModel->getModel();
            $objEntities = $objModel->findAll([
                'column' => [$strTable . '.' . $strColumn . ' REGEXP ?'],
                'value' => ['[[:<:]]' . $strValue . '[[:>:]]']
            ]);

            if ($objEntities) {
                while ($objEntities->next()) {
                    $arrIds[] = $objEntities->id;
                }
            }
        }

        return empty($arrIds) ? '0' : \serialize($arrIds);
    }

    public function __invoke($insertTag)
    {
        return $this->replace($insertTag);
    }
}