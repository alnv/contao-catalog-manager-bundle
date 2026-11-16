<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\Controller;
use Contao\Database;
use Contao\Input;
use Symfony\Contracts\Service\ResetInterface;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;
use Terminal42\DcMultilingualBundle\Driver;

class ChangelanguageNavigationListener implements ResetInterface
{
    private array $arrEntities = [];

    public function __invoke(ChangelanguageNavigationEvent $objEvent): void
    {
        if (empty($GLOBALS['CM_MASTER'])) {
            return;
        }

        $strTable = $GLOBALS['CM_MASTER']['_table'] ?? '';

        if (!$strTable) {
            return;
        }

        $objRoot = $objEvent->getNavigationItem()->getRootPage();
        $strLanguage = $objRoot->fallback ? '' : (string)$objRoot->rootLanguage;
        $arrEntity = $this->getEntity($strTable, Input::get('auto_item'));

        if (!$arrEntity['multilingual']) {
            $objEvent->getUrlParameterBag()->setUrlAttribute('auto_item', $arrEntity['alias']);

            return;
        }

        if ($strAlias = ($arrEntity['translations'][$strLanguage] ?? '')) {
            $objEvent->getUrlParameterBag()->setUrlAttribute('auto_item', $strAlias);
        }
    }

    public function reset(): void
    {
        $this->arrEntities = [];
    }

    private function getEntity(string $strTable, ?string $strAlias): array
    {
        $strKey = $strTable . "\0" . ($strAlias ?? "\0");

        if (isset($this->arrEntities[$strKey])) {
            return $this->arrEntities[$strKey];
        }

        Controller::loadDataContainer($strTable);

        $arrConfig = $GLOBALS['TL_DCA'][$strTable]['config'] ?? [];
        $objDatabase = Database::getInstance();
        $blnMultilingual = ($arrConfig['dataContainer'] ?? null) == 'Multilingual' || ($arrConfig['dataContainer'] ?? null) == Driver::class;
        $arrEntity = ['multilingual' => $blnMultilingual, 'alias' => null, 'translations' => []];

        if ($strAlias === null) {
            return $this->arrEntities[$strKey] = $arrEntity;
        }

        if (!$blnMultilingual) {
            $arrEntity['alias'] = $objDatabase->prepare('SELECT alias FROM ' . $strTable . ' WHERE `alias`=?')->limit(1)->execute($strAlias)->alias;

            return $this->arrEntities[$strKey] = $arrEntity;
        }

        $strLanguageColumn = $arrConfig['langColumnName'] ?? '';
        $strLangPidColumn = $arrConfig['langPid'] ?? '';

        $objCurrent = $objDatabase->prepare('SELECT id, `' . $strLangPidColumn . '` AS cm_lang_pid FROM ' . $strTable . ' WHERE `alias`=?')->limit(1)->execute($strAlias);

        if (!$objCurrent->numRows) {
            return $this->arrEntities[$strKey] = $arrEntity;
        }

        $intLangPid = $objCurrent->cm_lang_pid ?: $objCurrent->id;
        $objTranslations = $objDatabase
            ->prepare('SELECT id, alias, `' . $strLanguageColumn . '` AS cm_language, `' . $strLangPidColumn . '` AS cm_lang_pid FROM ' . $strTable . ' WHERE id=? OR `' . $strLangPidColumn . '`=?')
            ->execute($intLangPid, $intLangPid);

        while ($objTranslations->next()) {
            $strRowLanguage = (string)$objTranslations->cm_language;

            if (isset($arrEntity['translations'][$strRowLanguage])) {
                continue;
            }

            $blnMatches = $strRowLanguage === ''
                ? (int)$objTranslations->id === (int)$intLangPid
                : (int)$objTranslations->cm_lang_pid === (int)$intLangPid;

            if ($blnMatches) {
                $arrEntity['translations'][$strRowLanguage] = (string)$objTranslations->alias;
            }
        }

        return $this->arrEntities[$strKey] = $arrEntity;
    }
}
