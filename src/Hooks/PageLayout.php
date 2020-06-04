<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class PageLayout {

    public function generateMaster( \PageModel $objPage, \LayoutModel $objLayout, \PageRegular $objPageRegular ) {

        if ( !isset($_GET['auto_item']) && ! $_GET['auto_item']) {
            return null;
        }

        $this->getMasterByPageId($objPage->id);
        $this->setMetaInformation();
    }
    
    public function getMasterByPageId($strPageId,$strAlias=null) {

        if (!$strAlias) {
            $strAlias = \Input::get('auto_item');
        }
        $strTable = null;
        $strMasterPageId = $strPageId;
        $objModule = \Database::getInstance()->prepare( 'SELECT * FROM tl_module WHERE `type`=? AND cmMaster=? AND cmMasterPage=?' )->execute('listing','1',$strPageId);
        if ( !$objModule->numRows ) {
            $strTable = $this->searchTableAndReturnTable($strPageId);
            if (!$strTable) {
                return null;
            }
        } else {
            $strTable = $objModule->cmTable;
            $strMasterPageId = $objModule->cmMasterPage;
        }

        $GLOBALS['CM_MASTER'] = (new \Alnv\ContaoCatalogManagerBundle\Views\Master($strTable, [
            'alias' => $strAlias,
            'masterPage' => $strMasterPageId
        ]))->parse()[0];
    }

    protected function setMetaInformation() {

        if (!is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return null;
        }

        global $objPage;
        $objPage->pageTitle = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $objPage->description = strip_tags($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('teaser'));
    }

    protected function searchTableAndReturnTable($strPageId) {
        $objArticles = \ArticleModel::findByPid($strPageId);
        if ($objArticles == null) {
            return null;
        }
        while ($objArticles->next()) {
            if ($objArticles->cmContentElement) {
                if ($strTable = $this->getDetailFrontendModule(\ContentModel::findPublishedByPidAndTable($objArticles->cmContentElement,'tl_catalog_element'))) {
                    return $strTable;
                }
            }
            if ($strTable = $this->getDetailFrontendModule(\ContentModel::findPublishedByPidAndTable($objArticles->id, 'tl_article'))) {
                return $strTable;
            }
        }
        return null;
    }

    protected function getDetailFrontendModule($objContent) {
        if ($objContent==null) {
            return null;
        }
        while ($objContent->next()) {
            if ($objContent->type == 'module' && $objContent->module) {
                $objModule = \Database::getInstance()->prepare('SELECT * FROM tl_module WHERE id=?')->execute($objContent->module);
                if ($objModule == null) {
                    continue;
                }
                if ($objModule->cmMaster) {
                    return $objModule->cmTable;
                }
            }
        }
        return null;
    }
}