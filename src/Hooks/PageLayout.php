<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;


class PageLayout {


    public function generateMaster( \PageModel $objPage, \LayoutModel $objLayout, \PageRegular $objPageRegular ) {

        if ( !isset( $_GET['auto_item'] ) && ! $_GET['auto_item'] ) {

            return null;
        }

        $objModule = \Database::getInstance()->prepare( 'SELECT * FROM tl_module WHERE `type`=? AND cmMasterPage=?' )->execute('listing',$objPage->id);

        if ( !$objModule->numRows ) {

            return null;
        }

        $GLOBALS['CM_MASTER'] = (new \Alnv\ContaoCatalogManagerBundle\Views\Master( $objModule->cmTable, [

            'alias' => \Input::get('auto_item'),
            'masterPage' => $objModule->cmMasterPage,
            'id' => $objModule->id
        ]))->parse()[0];
    }
}