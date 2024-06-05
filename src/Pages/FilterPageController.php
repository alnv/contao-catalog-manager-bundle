<?php

namespace Alnv\ContaoCatalogManagerBundle\Pages;

use Contao\Input;
use Contao\PageModel;
use Contao\Environment;
use Contao\FrontendIndex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\CoreBundle\Exception\PageNotFoundException;

class FilterPageController
{

    public function __invoke(Request $request, PageModel $pageModel): Response
    {

        Input::resetUnusedGet();

        $arrFragments = Toolkit::getCurrentPathInfo($pageModel->alias);

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