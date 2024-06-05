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
            $blnTouch = false;
            $blnUseCsv = false;
            $strDefault = null;
            $blnUseDefault = false;
            $varValue = Toolkit::getValueFromUrl(Toolkit::getFilterValue($arrFragments[1]));

            if (isset($arrFragments[2]) && strpos($arrFragments[2], '?') !== false) {
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
                        case 'touch':
                            $blnTouch = (bool)$strOption;
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

            if (Validator::isDate($varValue)) {
                $varValue = (new Date($varValue, $objPage->dateFormat))->dayBegin;
            }

            if (Validator::isDatim($varValue)) {
                $varValue = (new Date($varValue, $objPage->dateFormat))->dayBegin;
            }

            if ($blnTouch && !empty($varValue)) {
                $_varValue = StringUtil::deserialize($varValue);
                if (is_array($_varValue)) {
                    $arrValues = [];
                    foreach ($_varValue as $strKey => $strValue) {
                        $arrValues[$strKey] = '[[:<:]]' . $strValue . '[[:>:]]';
                    }
                    $varValue = serialize($arrValues);
                } elseif (is_string($_varValue)) {
                    $varValue = '[[:<:]]' . $_varValue . '[[:>:]]';
                }
            }

            if ($blnUseCsv) {
                $varValue = serialize(explode(',', $varValue));
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

            return $varValue;
        }

        return false;
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