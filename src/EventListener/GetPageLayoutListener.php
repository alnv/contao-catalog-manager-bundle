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

    public function getMasterByPageId($strPageId, $strAlias=null) {

        $strMasterPageId = $strPageId;
        $objModule = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE `type`=? AND cmMaster=? AND cmMasterPage=?')->execute('listing','1',$strPageId);

        if (!$objModule->numRows) {

            $strTable = $this->searchTableAndReturnTable($strPageId);

            if (!$strTable) {
                return null;
            }

        } else {
            $strTable = $objModule->cmTable;
            $strMasterPageId = $objModule->cmMasterPage;
        }

        if (!$strAlias) {
            $strAlias = Input::get('auto_item');
        }

        $GLOBALS['CM_MASTER'] = (new Master($strTable, [
            'alias' => $strAlias,
            'masterPage' => $strMasterPageId
        ]))->parse();

        $GLOBALS['CM_MASTER'] = $GLOBALS['CM_MASTER'][0] ?? [];

        $this->setMetaInformation($strTable);
    }

    protected function setMetaInformation($strTable = null)
    {

        if (!is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return null;
        }

        global $objPage;

        $objPage->pageTitle = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $objPage->description = strip_tags($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('teaser'));

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