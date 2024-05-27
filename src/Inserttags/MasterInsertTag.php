<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\StringUtil;

class MasterInsertTag
{

    public function replace($strFragment)
    {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && strtoupper($arrFragments[0]) == 'MASTER' && isset($arrFragments[1])) {

            $strDefault = $arrFragments[2] ?? '';

            if (empty($GLOBALS['CM_MASTER'])) {
                return $strDefault;
            }

            $varValue = '';
            $strFieldname = explode('>', StringUtil::decodeEntities(($arrFragments[1] ?: '')));

            switch ($strFieldname[0]) {
                case 'getParent':
                    $varValue = $GLOBALS['CM_MASTER']['getParent']()[$strFieldname[1]] ?? '';
                    break;
                case 'origin':
                    $varValue = $GLOBALS['CM_MASTER']['origin'][$strFieldname[1]] ?? '';
                    break;
                case 'getRelated':
                case 'getArray':
                    if ($strFieldname[0] == 'getRelated') {
                        $arrArray = $GLOBALS['CM_MASTER']['getRelated']($strFieldname[1]);
                    } else {
                        $arrArray = $GLOBALS['CM_MASTER'][$strFieldname[1]] ?? [];
                    }
                    if (is_array($arrArray) && isset($strFieldname[2])) {
                        $arrValues = [];
                        foreach ($arrArray as $arrEntity) {
                            $arrValues[] = $arrEntity[$strFieldname[2]] ? Toolkit::parse($arrEntity[$strFieldname[2]]) : '';
                        }
                        $varValue = implode(',', $arrValues);
                    }
                    break;
                default;
                    $varValue = $GLOBALS['CM_MASTER'][$strFieldname[0]] ?? '';
                    break;
            }

            if ($varValue === '' || $varValue === null) {
                return $strDefault;
            }

            if (is_array($varValue)) {
                if ($strValue = Toolkit::parse($varValue)) {
                    return $strValue;
                }
                if ($strImage = Toolkit::parseImage($varValue)) {
                    return $strImage;
                }
            }

            return $varValue ? (string)$varValue : $strDefault;
        }

        return false;
    }

    public function __invoke($insertTag)
    {
        return $this->replace($insertTag);
    }
}