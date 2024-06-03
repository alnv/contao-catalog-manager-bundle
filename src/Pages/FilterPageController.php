<?php

namespace Alnv\ContaoCatalogManagerBundle\Pages;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\FrontendIndex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alnv\ContaoCatalogManagerBundle\Helper\Getters;

class FilterPageController
{

    public function __invoke(Request $request, PageModel $pageModel): Response
    {

        Input::resetUnusedGet();

        $arrFragments = [];
        foreach (array_filter(explode('/', $request->getPathInfo())) as $strFragment) {
            if ($pageModel->alias == $strFragment) {
                continue;
            }
            $arrFragments[] = $strFragment;
        }

        foreach ($_GET as $strGet => $strValue) {
            Input::setGet($strGet, null);
        }

        $arrPageFilters = Getters::getPageFiltersByPageId($pageModel->id);
        foreach ($arrPageFilters as $intIndex => $objPageFilter) {
            $objPageFilter->setActiveUrlFragment(($arrFragments[$intIndex]??''));
            if (($arrFragments[$intIndex]??'') && !$objPageFilter->activeUrlFragmentExists()) {
                throw new PageNotFoundException('Page not found: ' . Environment::get('url'));
            }
            unset($arrFragments[$intIndex]);
        }

        if (!empty($arrFragments)) {
            $strAutoItem = array_values($arrFragments)[0];
            Input::setGet('auto_item', $strAutoItem);
        }

        return (new FrontendIndex())->renderPage($pageModel);
    }
}