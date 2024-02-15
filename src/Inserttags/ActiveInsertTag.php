<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;

class ActiveInsertTag extends \System {

    public function replace( $strFragment ) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'ACTIVE' && isset($arrFragments[1])) {

            global $objPage;

            $strMode = null;
            $strDefault = null;
            $blnUseDefault = false;
            $blnUseCsv = false;
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
                        case 'csv':
                            if ($varValue !== '') {
                                $blnUseCsv = true;
                            }
                            break;
                    }
                }
            }

            if ($blnUseDefault && ($varValue === '' || $varValue === null)) {
                $varValue = $strDefault;
            }

            if (\Validator::isDate($varValue)) {
                $varValue = (new \Date($varValue, $objPage->dateFormat))->dayBegin;
            }

            if (\Validator::isDatim($varValue)) {
                $varValue = (new \Date($varValue, $objPage->dateFormat))->dayBegin;
            }

            if ($blnUseCsv) {
                $varValue = serialize(explode(',', $varValue));
            }

            if (isset($GLOBALS['TL_HOOKS']['replaceActiveInserttag']) && is_array($GLOBALS['TL_HOOKS']['replaceActiveInserttag'])) {
                foreach ($GLOBALS['TL_HOOKS']['replaceActiveInserttag'] as $arrCallback) {
                    $this->import($arrCallback[0]);
                    $varValue = $this->{$arrCallback[0]}->{$arrCallback[1]}($varValue, $arrFragments);
                }
            }

            if ($strMode) {
                if ($strMode == 'FE' && TL_MODE != $strMode) {
                    return '';
                }
                if ($strMode == 'BE' && TL_MODE != $strMode) {
                    return '';
                }
            }

            return $varValue;
        }

        return false;
    }
}