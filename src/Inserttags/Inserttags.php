<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Database;
use Contao\Date;
use Contao\Config;
use Contao\FrontendUser;

class Inserttags
{

    public function replace($strFragments)
    {

        $arrFragments = explode('::', $strFragments);

        if (!isset($arrFragments[0])) {
            return false;
        }

        $strType = strtoupper($arrFragments[0]);

        switch ($strType) {
            case 'CM-USER':
                if (!FrontendUser::getInstance()->id) {
                    return '';
                }
                $objUser = FrontendUser::getInstance();
                $objDbUser = Database::getInstance()->prepare('SELECT * FROM tl_member WHERE id=?')->limit(1)->execute($objUser->id);
                $strField = $arrFragments[1] ?: '';
                if (!$strField) {
                    return '';
                }
                return $objDbUser->{$strField};
            case 'TIMESTAMP':
                $strMethod = $arrFragments[1] ?? 'tstamp';
                $strStrToTimeParameter = $arrFragments[2] ?: '';
                $strCurrentTstamp = \time();
                if ($strStrToTimeParameter) {
                    $strCurrentTstamp = \strtotime($strStrToTimeParameter, $strCurrentTstamp);
                }
                $strDate = \date(Config::get('datimFormat'), $strCurrentTstamp);
                return (new Date($strDate, Config::get('datimFormat')))->{$strMethod};
            case 'LAST-ADDED-MASTER-VIEW-IDS':
                $strTable = $arrFragments[1] ?? '';
                if (!$strTable) {
                    return '0';
                }
                $arrIds = Toolkit::getLastAddedByTypeAndTable('view-master', $strTable);
                if (empty($arrIds)) {
                    return '0';
                }
                return serialize($arrIds);
        }

        return false;
    }

    public function __invoke($insertTag)
    {
        return $this->replace($insertTag);
    }
}