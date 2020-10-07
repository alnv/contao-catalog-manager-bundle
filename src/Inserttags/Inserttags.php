<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

class Inserttags {

    public function replace($strFragments) {

        $arrFragments = explode('::', $strFragments);
        if (!isset($arrFragments[0])) {
            return false;
        }

        switch ($arrFragments[0]) {
            case 'cmUser':
                if (!FE_USER_LOGGED_IN) {
                    return '';
                }
                $objUser = \FrontendUser::getInstance();
                $objDbUser = \Database::getInstance()->prepare('SELECT * FROM tl_member WHERE id=?')->limit(1)->execute($objUser->id);
                $strField = $arrFragments[1] ?: '';
                if (!$strField) {
                    return '';
                }
                return $objDbUser->{$strField};
        }

        return false;
    }
}