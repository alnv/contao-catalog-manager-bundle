<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class MasterInsertTag {

    public function replace($strFragment) {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'MASTER' && isset($arrFragments[1])) {

            if (empty($GLOBALS['CM_MASTER'])) {

                \System::log('Master entity do not exist.', __METHOD__, TL_GENERAL);
                return '';
            }

            return (string) $GLOBALS['CM_MASTER'][$arrFragments[1]];
        }

        return false;
    }
}