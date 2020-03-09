<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class PageLayout {

    public function generateMaster( \PageModel $objPage, \LayoutModel $objLayout, \PageRegular $objPageRegular ) {

        if ( !isset( $_GET['auto_item'] ) && ! $_GET['auto_item'] ) {
            return null;
        }

        $this->getMasterByPageId($objPage->id);
    }
    
    public function getMasterByPageId($strPageId,$strAlias=null) {

        if (!$strAlias) {
            $strAlias = \Input::get('auto_item');
        }
        $objModule = \Database::getInstance()->prepare( 'SELECT * FROM tl_module WHERE `type`=? AND cmMaster=? AND cmMasterPage=?' )->execute('listing','1',$strPageId);
        if ( !$objModule->numRows ) {
            return null;
        }
        $GLOBALS['CM_MASTER'] = (new \Alnv\ContaoCatalogManagerBundle\Views\Master( $objModule->cmTable, [
            'alias' => $strAlias,
            'masterPage' => $objModule->cmMasterPage,
            'id' => $objModule->id
        ]))->parse()[0];
    }
}