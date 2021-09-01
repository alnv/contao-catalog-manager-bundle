<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class MasterInsertTag {

    public function replace($strFragment) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'MASTER' && isset($arrFragments[1])) {

            $strDefault = $arrFragments[2] ?: '';

            if (empty($GLOBALS['CM_MASTER'])) {
                return $strDefault;
            }

            $varValue = $GLOBALS['CM_MASTER'][$arrFragments[1]];
            if ($varValue == null) {
                return $strDefault;
            }
            if (is_array($varValue)) {
                if ($strValue = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parse($varValue)) {
                    return $strValue;
                }
                if ($strImage = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseImage($varValue)) {
                    return \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseImage($varValue);
                }
            }

            return $varValue ? (string) $varValue : $strDefault;
        }

        return false;
    }
}