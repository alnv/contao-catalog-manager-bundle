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
            case 'TIMESTAMP':
            case 'timestamp':
                $strMethod = $arrFragments[1] ?: 'tstamp';
                $strStrToTimeParameter = $arrFragments[2] ?: '';
                if ($strStrToTimeParameter) {
                    return strtotime($strStrToTimeParameter, (new \Date())->{$strMethod});
                } else {
                    return (new \Date())->{$strMethod};
                }
            case 'LAST-ADDED-MASTER-VIEW-IDS':
                $strTable = $arrFragments[1] ?: '';
                if (!$strTable) {
                    return '0';
                }
                $arrIds = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLastAddedByTypeAndTable('view-master', $strTable);
                if (empty($arrIds)) {
                    return '0';
                }
                return serialize($arrIds);

        }

        return false;
    }
}