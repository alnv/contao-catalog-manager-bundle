<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;

class GenerateBreadcrumbListener
{

    public function __invoke(array $arrItems, Module $module): array
    {

        $arrActiveUrlFragmentItems = $this->getActiveUrlFragments(($arrItems[\count($arrItems) - 1]['data'] ?? []));

        if (!empty($arrActiveUrlFragmentItems)) {

            $arrItems[\count($arrItems) - 1] = $this->resetLastItemHref($arrItems[\count($arrItems) - 1]);

            foreach ($arrActiveUrlFragmentItems as $arrItem) {
                $arrItems[] = $arrItem;
            }

            foreach ($arrItems as $intIndex => $arrItem) {
                $arrItems[$intIndex]['isActive'] = $intIndex === \count($arrItems) - 1;
            }
        }

        if (!\is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return $arrItems;
        }

        $intLastItemId = \count($arrItems) - 1;
        $arrItems[$intLastItemId] = $this->resetLastItemHref($arrItems[$intLastItemId]);
        $blnRequireItem = $arrItems[$intLastItemId]['data']['requireItem'] ?? false;

        $arrItem = [];
        $arrItem['isActive'] = true;
        $arrItem['href'] = $GLOBALS['CM_MASTER']['masterUrl'];
        $arrItem['link'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title') ?: '';
        $arrItem['title'] = $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title') ?: '';
        $arrItems[$intLastItemId]['data']['title'] = $arrItem['title'];
        $arrItem['data'] = $arrItems[$intLastItemId]['data'];

        if ($blnRequireItem) {
            $arrItems[$intLastItemId] = $arrItem;
        } else {
            $arrItems[] = $arrItem;
        }

        return $arrItems;
    }

    protected function resetLastItemHref($arrItem): array
    {

        $arrItem['isActive'] = false;
        $strPageId = $arrItem['data']['id'] ?? '';
        $blnRequireItem = $arrItem['data']['requireItem'] ?? false;

        if (($objPage = PageModel::findByPk($strPageId)) && !$blnRequireItem) {
            try {
                $arrItem['href'] = $objPage->getFrontendUrl();
                $arrItem['title'] = $objPage->pageTitle;
                $arrItem['isRoot'] = false;
            } catch (\Exception $objError) {
                //
            }
        }

        return $arrItem;
    }

    protected function getActiveUrlFragments($arrData = []): array
    {

        global $objPage;

        $arrItems = [];

        if ($objPage->type !== 'filter') {
            return $arrItems;
        }

        $arrPrevUrlFragments = [];
        $arrFragments = Toolkit::getCurrentPathInfo($objPage->alias, $objPage->language);

        foreach (Getters::getPageFiltersByPageId($objPage->id) as $intIndex => $objPageFilter) {

            if (!($arrFragments[$intIndex] ?? '')) {
                continue;
            }

            $strActiveUrlFragment = StringUtil::decodeEntities(($arrFragments[$intIndex] ?? ''));
            $arrFilterPage = $objPageFilter->getPageFilterArray();
            $strUrlFragment = $objPageFilter->parseActiveUrlFragment($strActiveUrlFragment);

            $strTable = $arrFilterPage['table'] ?? '';
            $strColumn = $arrFilterPage['column'] ?? '';
            $strColumnTable = $GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?? $strTable;

            $arrMasterEntity = (new Listing($strTable, [
                'column' => [$strColumnTable . '.`' . $strColumn . '` REGEXP ?'],
                'value' => ['[[:<:]]' . $strActiveUrlFragment . '[[:>:]]'],
                'fastMode' => true,
                'ignoreVisibility' => true,
                'limit' => 1,
            ]))->parse()[0] ?? [];

            if (empty($arrMasterEntity)) {
                continue;
            }

            try {
                $arrPrevUrlFragments[] = $strUrlFragment;
                $strUrl = $objPage->getFrontendUrl('/' . implode('/', $arrPrevUrlFragments));
            } catch (\Exception $objError) {
                continue;
            }

            $strTitle = ($arrMasterEntity[$strColumn] ?? '') ?? '';
            $strAlias = $arrMasterEntity['roleResolver']()->getFieldByRole('alias');

            if ($strAlias === $strColumn || is_numeric($strTitle)) {
                $strTitle = $arrMasterEntity['roleResolver']()->getValueByRole('title');
            }

            $strTitle = Toolkit::parse($strTitle, '-');
            $strTeaser = strip_tags($arrMasterEntity['roleResolver']()->getValueByRole('teaser') ?: '');

            $arrData['pageTitle'] = $strTitle;
            $arrData['title'] = $strTitle;
            $arrData['description'] = $strTeaser;
            $arrData['alias'] = $strUrlFragment;
            $arrData['href'] = $strUrl;

            $arrItems[] = [
                'href' => $strUrl,
                'link' => $strTitle,
                'title' => $strTitle,
                'isActive' => false
            ];

            $objPage->pageTitle = $strTitle;
            $objPage->description = $strTeaser ?: '';
        }

        return $arrItems;
    }
}