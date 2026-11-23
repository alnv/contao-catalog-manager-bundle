<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Database;
use Contao\Input;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\System;

class GetPageLayoutListener
{
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if (!isset($_GET['auto_item'])) {
            return;
        }

        $this->getMasterByPageId($pageModel->id);
    }

    public function getMasterByPageId($strPageId, $strAlias = null): void
    {
        $strMasterPageId = $strPageId;
        $objModule = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE `type`=? AND cmMaster=? AND cmMasterPage=?')->execute('listing', '1', $strPageId);

        if (!$objModule->numRows) {
            $strTable = $this->searchTableAndReturnTable($strPageId);
            if (!$strTable) {
                return;
            }

        } else {
            $strTable = $objModule->cmTable;
            $strMasterPageId = $objModule->cmMasterPage;
        }

        if (!$strAlias) {
            $strAlias = Input::xssClean($_GET['auto_item']);
        }

        $arrMaster = (new Master($strTable, [
            'alias' => $strAlias,
            'masterPage' => $strMasterPageId
        ]))->parse();

        $arrMaster = $arrMaster[0] ?? [];

        if (empty($arrMaster)) {
            return;
        }

        $GLOBALS['CM_MASTER'] = $arrMaster;
        $this->setMetaInformation($strTable);
    }

    protected function setMetaInformation($strTable = null): void
    {

        if (!is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return;
        }

        global $objPage;

        $objPage->pageTitle = strip_tags($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('metaTitle') ?: $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title'));
        $objPage->description = strip_tags(($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('metaDescription') ?: $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('description')));

        if (isset($GLOBALS['TL_HOOKS']['setMetaInformation']) && is_array($GLOBALS['TL_HOOKS']['setMetaInformation'])) {
            foreach ($GLOBALS['TL_HOOKS']['setMetaInformation'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($objPage, $strTable);
            }
        }
    }

    protected function searchTableAndReturnTable($strPageId)
    {

        $objArticles = ArticleModel::findByPid($strPageId);

        if ($objArticles == null) {
            return null;
        }

        while ($objArticles->next()) {
            if ($strTable = $this->getDetailFrontendModule(ContentModel::findPublishedByPidAndTable($objArticles->id, 'tl_article'))) {
                return $strTable;
            }
        }

        $objContent = ContentModel::findPublishedByPidAndTable($objArticles->id, 'tl_article');
        if ($objContent == null) {
            return null;
        }

        while ($objContent->next()) {

            if (!in_array($objContent->type, ['listview'])) {
                continue;
            }

            if (!$objContent->cmTable) {
                continue;
            }

            return $objContent->cmTable;
        }

        return null;
    }

    protected function getDetailFrontendModule($objContent)
    {
        if ($objContent == null) {
            return null;
        }

        while ($objContent->next()) {

            if ($objContent->type == 'module' && $objContent->module) {

                $objModule = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE id=?')->execute($objContent->module);

                if ($objModule == null) {
                    continue;
                }

                if ($objModule->cmMaster || $objModule->type == 'master') {
                    return $objModule->cmTable;
                }
            }
        }

        return null;
    }
}