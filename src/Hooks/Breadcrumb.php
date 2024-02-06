<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

class Breadcrumb {

    public function generateDetailPage($arrItems) {

        if (!is_array($GLOBALS['CM_MASTER']) ||  empty($GLOBALS['CM_MASTER'])) {
            return $arrItems;
        }

        $intLastItemId = count($arrItems) -1;
        $arrItems[$intLastItemId]['isActive'] = false;

        $strPageId = $arrItems[$intLastItemId]['data']['id'] ?? '';
        $blnRequireItem = $arrItems[$intLastItemId]['data']['requireItem'] ?? false;

        if (($objPage = \PageModel::findByPk($strPageId)) && !$blnRequireItem) {
            $arrItems[$intLastItemId]['href'] = $objPage->getFrontendUrl();
        }

        $arrItem = [];
        $arrItem['isActive'] = true;
        $arrItem['href'] = $GLOBALS['CM_MASTER']['masterUrl'];
        $arrItem['link'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItem['title'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItems[$intLastItemId]['data']['title'] = $arrItem['title'];
        $arrItem['data'] = $arrItems[$intLastItemId]['data'];

        if ($blnRequireItem) {
            $arrItems[$intLastItemId] = $arrItem;
        } else {
            $arrItems[] = $arrItem;
        }

        return $arrItems;
    }
}