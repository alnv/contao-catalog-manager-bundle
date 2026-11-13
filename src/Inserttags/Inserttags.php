<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Controller;
use Contao\Database;
use Contao\Date;
use Contao\Config;
use Contao\FrontendUser;
use Contao\StringUtil;

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
                $strField = (string)($arrFragments[1] ?? '');
                if (!$this->isAllowedMemberField($strField)) {
                    return '';
                }
                $objUser = FrontendUser::getInstance();
                $objDbUser = Database::getInstance()->prepare('SELECT * FROM tl_member WHERE id=?')->limit(1)->execute($objUser->id);
                $varValue = $objDbUser->{$strField};
                return \is_scalar($varValue) ? StringUtil::specialchars((string)$varValue) : '';
            case 'TIMESTAMP':
                $strMethod = $arrFragments[1] ?? 'tstamp';
                $strStrToTimeParameter = $arrFragments[2] ?? '';
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

    /** Spalten von tl_member, die nie per {{cm-user::*}} ausgegeben werden */
    private const DENIED_MEMBER_FIELDS = [
        'password', 'secret', 'backupCodes', 'trustedTokenVersion', 'session', 'activation',
        'loginAttempts', 'locked', 'lastLogin', 'currentLogin', 'useTwoFactor', 'groups', 'autologin',
    ];

    private function isAllowedMemberField(string $strField): bool
    {
        if (!\preg_match('/^[A-Za-z0-9_]{1,64}$/', $strField)) {
            return false;
        }

        if (\in_array(\strtolower($strField), \array_map('strtolower', self::DENIED_MEMBER_FIELDS), true)) {
            return false;
        }

        // Felder, die im DCA als sensibel markiert sind (z. B. inputType password), ebenfalls sperren
        Controller::loadDataContainer('tl_member');
        $arrField = $GLOBALS['TL_DCA']['tl_member']['fields'][$strField] ?? null;

        return !\is_array($arrField) || ($arrField['inputType'] ?? '') !== 'password';
    }

    public function __invoke($insertTag)
    {
        return $this->replace($insertTag);
    }
}