<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\Module;
use Contao\PageModel;

class GenerateBreadcrumbListener
{

    public function __invoke(array $arrItems, Module $module): array
    {
        if (!is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return $arrItems;
        }

        $intLastItemId = count($arrItems) - 1;
        $arrItems[$intLastItemId]['isActive'] = false;

        $strPageId = $arrItems[$intLastItemId]['data']['id'] ?? '';
        if ($objPage = PageModel::findByPk($strPageId)) {
            $arrItems[$intLastItemId]['href'] = $objPage->getFrontendUrl();
        }

        $arrItem = [];
        $arrItem['isActive'] = true;
        $arrItem['href'] = $GLOBALS['CM_MASTER']['masterUrl'];
        $arrItem['link'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItem['title'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title');
        $arrItems[$intLastItemId]['data']['title'] = $arrItem['title'];
        $arrItem['data'] = $arrItems[$intLastItemId]['data'];

        if ($arrItems[$intLastItemId]['data']['requireItem']) {
            $arrItems[$intLastItemId] = $arrItem;
        } else {
            $arrItems[] = $arrItem;
        }

        return $arrItems;
    }
}