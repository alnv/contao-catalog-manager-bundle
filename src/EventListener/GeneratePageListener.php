<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\System;

class GeneratePageListener
{

    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        $this->setMetaInformation();
    }

    protected function setMetaInformation()
    {

        if (!is_array($GLOBALS['CM_MASTER']) || empty($GLOBALS['CM_MASTER'])) {
            return null;
        }

        $GLOBALS['objPage']->pageTitle = strip_tags($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('metaTitle') ?: $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('title'));
        $GLOBALS['objPage']->description = strip_tags(($GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('metaDescription') ?: $GLOBALS['CM_MASTER']['roleResolver']()->getValueByRole('description')));

        $objResponseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();
        $objHeadBag = $objResponseContext->get(HtmlHeadBag::class);
        $objHeadBag->setTitle($GLOBALS['objPage']->pageTitle ?: '');
        $objHeadBag->setMetaDescription($GLOBALS['objPage']->description ?: '');

        if (isset($GLOBALS['TL_HOOKS']['setMetaInformation']) && is_array($GLOBALS['TL_HOOKS']['setMetaInformation'])) {
            foreach ($GLOBALS['TL_HOOKS']['setMetaInformation'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($GLOBALS['objPage']);
            }
        }
    }
}