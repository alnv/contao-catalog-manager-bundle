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

            $varValue = '';
            $strFieldname = explode('>', \StringUtil::decodeEntities(($arrFragments[1]?:'')));

            switch ($strFieldname[0]) {
                case 'getParent':
                    $varValue = $GLOBALS['CM_MASTER']['getParent']()[$strFieldname[1]];
                    break;
                case 'getRelated':
                case 'getArray':
                    if ($strFieldname[0] == 'getRelated') {
                        $arrArray = $GLOBALS['CM_MASTER']['getRelated']($strFieldname[1]);
                    } else {
                        $arrArray = $GLOBALS['CM_MASTER'][$strFieldname[1]];
                    }
                    if (is_array($arrArray) && isset($strFieldname[2])) {
                        $arrValues = [];
                        foreach ($arrArray as $arrEntity) {
                            $arrValues[] = $arrEntity[$strFieldname[2]] ? \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parse($arrEntity[$strFieldname[2]]) : '';
                        }
                        $varValue = implode(',', $arrValues);
                    }
                    break;
                default;
                    $varValue = $GLOBALS['CM_MASTER'][$strFieldname[0]];
                    break;
            }

            if ($varValue == null) {
                return $strDefault;
            }

            if (is_array($varValue)) {
                if ($strValue = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parse($varValue)) {
                    return $strValue ?: $strDefault;
                }
                if ($strImage = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parseImage($varValue)) {
                    return $strImage ?: $strDefault;
                }
            }

            return $varValue ? (string) $varValue : $strDefault;
        }

        return false;
    }
}