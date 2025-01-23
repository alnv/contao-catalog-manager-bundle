<?php

namespace Alnv\ContaoCatalogManagerBundle\Pages;

use Alnv\ContaoCatalogManagerBundle\Helper\Getters;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendIndex;
use Contao\Input;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FilterPageController
{

    public function __invoke(Request $request, PageModel $pageModel): Response
    {

        $GLOBALS['TL_LANGUAGE'] = $pageModel->language ?: $GLOBALS['TL_LANGUAGE'];

        Input::resetUnusedGet();

        $arrPageFilters = Getters::getPageFiltersByPageId($pageModel->id);
        $arrFragments = Toolkit::getCurrentPathInfo($pageModel->alias, $pageModel->language);

        if (isset($_GET['auto_item'])) {
            Input::setGet('auto_item', null);
        }

        foreach ($arrPageFilters as $intIndex => $objPageFilter) {

            $objPageFilter->setActiveUrlFragment(($arrFragments[$intIndex] ?? ''));

            if (($arrFragments[$intIndex] ?? '') && !$objPageFilter->activeUrlFragmentExists()) {
                throw new PageNotFoundException('Page not found: ' . Environment::get('url'));
            }

            unset($arrFragments[$intIndex]);
        }

        if (!empty($arrFragments)) {
            $strAutoItem = \array_values($arrFragments)[0];
            Input::setGet('auto_item', $strAutoItem);
        }

        return (new FrontendIndex())->renderPage($pageModel);
    }
}