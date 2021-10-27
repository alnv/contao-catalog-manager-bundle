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

            $strFieldname = explode('>', \StringUtil::decodeEntities(($arrFragments[1]?:'')));

            switch ($strFieldname[0]) {
                case 'getParent':
                        $varValue = $GLOBALS['CM_MASTER']['getParent']()[$strFieldname[1]];
                        break;
                case 'getArray':
                        if (is_array($GLOBALS['CM_MASTER'][$strFieldname[1]]) && isset($strFieldname[2])) {
                            $strValue = '';
                            foreach ($GLOBALS['CM_MASTER'][$strFieldname[1]] as $arrEntity) {
                                $strValue .= $arrEntity[$strFieldname[2]] ?: '';
                            }
                            $varValue = $strValue;
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