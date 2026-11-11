<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\Database;
use Contao\Date;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\System;

class GetPageLayoutListener
{
    /**
     * Bereits aufgelöste Detailansichten pro Request (Seite + Alias), damit Layout-Hook und
     * AJAX-Controller nicht mehrfach parsen.
     *
     * @var array<string, array{table: string, alias: string}|null>
     */
    private static array $arrResolved = [];

    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if (!isset($_GET['auto_item'])) {
            return;
        }

        $this->getMasterByPageId($pageModel->id);
    }

    public function getMasterByPageId($strPageId, $strAlias = null)
    {
        if (!$strAlias) {
            $strAlias = ($_GET['auto_item'] ?? '') ? Input::get('auto_item') : '';
        }

        $strKey = (int)$strPageId . '|' . $strAlias;

        if (\array_key_exists($strKey, self::$arrResolved)) {
            return null;
        }

        self::$arrResolved[$strKey] = null;

        $strMasterPageId = $strPageId;
        $objModule = Database::getInstance()
            ->prepare('SELECT cmTable, cmMasterPage FROM tl_module WHERE `type`=? AND cmMaster=? AND cmMasterPage=?')
            ->limit(1)
            ->execute('listing', '1', $strPageId);

        if (!$objModule->numRows) {

            $strTable = $this->searchTableAndReturnTable($strPageId);

            if (!$strTable) {
                return null;
            }

        } else {
            $strTable = $objModule->cmTable;
            $strMasterPageId = $objModule->cmMasterPage;
        }

        $arrMaster = (new Master($strTable, [
            'alias' => $strAlias,
            'masterPage' => $strMasterPageId
        ]))->parse();
        $arrMaster = $arrMaster[0] ?? [];

        if (!empty($arrMaster)) {
            $GLOBALS['CM_MASTER'] = $arrMaster;
            // Kontext merken, damit das Detail-Modul (MasterModule) das Ergebnis wiederverwenden kann
            $GLOBALS['CM_MASTER_CONTEXT'] = ['table' => (string)$strTable, 'alias' => (string)$strAlias];
            self::$arrResolved[$strKey] = $GLOBALS['CM_MASTER_CONTEXT'];
        }

        $this->setMetaInformation($strTable);
    }

    protected function setMetaInformation($strTable = null)
    {

        if (!is_array($GLOBALS['CM_MASTER'] ?? null) || empty($GLOBALS['CM_MASTER'])) {
            return null;
        }

        global $objPage;

        $objRoleResolver = $GLOBALS['CM_MASTER']['roleResolver']();

        $objPage->pageTitle = strip_tags($objRoleResolver->getValueByRole('metaTitle') ?: $objRoleResolver->getValueByRole('title'));
        $objPage->description = strip_tags(($objRoleResolver->getValueByRole('metaDescription') ?: $objRoleResolver->getValueByRole('description')));

        if (isset($GLOBALS['TL_HOOKS']['setMetaInformation']) && is_array($GLOBALS['TL_HOOKS']['setMetaInformation'])) {
            foreach ($GLOBALS['TL_HOOKS']['setMetaInformation'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($objPage, $strTable);
            }
        }
    }

    /**
     * Sucht auf der Seite die Katalog-Tabelle der Detailansicht – mit einer einzigen Abfrage
     * statt einer Abfrage pro Artikel und pro Modul (N+1).
     *
     * Reihenfolge wie bisher: zuerst ein Detail-Modul (cmMaster bzw. Typ "master"), danach ein
     * Inhaltselement "listview". Anders als vorher werden dabei alle Artikel der Seite berücksichtigt
     * (vorher wurden die listview-Elemente nur im letzten Artikel gesucht).
     */
    protected function searchTableAndReturnTable($strPageId)
    {
        $intTime = Date::floorToMinute();

        $objElements = Database::getInstance()
            ->prepare(
                "SELECT c.type, c.cmTable AS contentTable, m.type AS moduleType, m.cmMaster, m.cmTable AS moduleTable
                FROM tl_content c
                INNER JOIN tl_article a ON a.id=c.pid AND c.ptable='tl_article'
                LEFT JOIN tl_module m ON c.type='module' AND m.id=c.module
                WHERE a.pid=? AND c.invisible=0
                    AND (c.start='' OR c.start<=?) AND (c.stop='' OR c.stop>?)
                    AND (c.type='listview' OR c.type='module')
                ORDER BY a.sorting, c.sorting"
            )
            ->execute((int)$strPageId, $intTime, $intTime + 60);

        $strListviewTable = null;

        while ($objElements->next()) {

            if ($objElements->type === 'module' && $objElements->moduleTable && ($objElements->cmMaster || $objElements->moduleType === 'master')) {
                return $objElements->moduleTable;
            }

            if ($strListviewTable === null && $objElements->type === 'listview' && $objElements->contentTable) {
                $strListviewTable = $objElements->contentTable;
            }
        }

        return $strListviewTable;
    }
}
