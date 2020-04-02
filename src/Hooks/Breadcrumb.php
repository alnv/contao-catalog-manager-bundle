<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class Breadcrumb {

    public function generateDetailPage($arrItems) {

        if ( !is_array($GLOBALS['CM_MASTER']) ||  empty($GLOBALS['CM_MASTER']) ) {
            return $arrItems;
        }

        $intLastItemId = count( $arrItems ) -1;
        $arrItems[ $intLastItemId]['isActive'] = false;
        $arrItem = [];
        $arrItem['isActive'] = true;
        $arrItem['href'] = $GLOBALS['CM_MASTER']['masterUrl'];
        $arrItem['link'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItem['title'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItem['data'] = $arrItems[ $intLastItemId ]['data'];
        $arrItems[] = $arrItem;

        return $arrItems;
    }
}