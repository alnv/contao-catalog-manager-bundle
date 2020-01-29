<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;


class Breadcrumb {


    public function generateDetailPage( $arrItems ) {

        if ( !is_array( $GLOBALS['CM_MASTER'] ) ||  empty( $GLOBALS['CM_MASTER'] ) ) {

            return null;
        }

        $arrItem = [];
        $arrItem['isActive'] = true;
        $arrItem['href'] = $GLOBALS['CM_MASTER']['masterUrl'];
        $arrItem['data'] = [];
        $arrItem['link'] = $GLOBALS['CM_MASTER']['title']; // @todo role resolver
        $arrItem['title'] = $GLOBALS['CM_MASTER']['title']; // @todo role resolver

        $arrItems[] = $arrItem;

        return $arrItems;
    }
}