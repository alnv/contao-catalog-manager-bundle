<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\PageModel;
use Contao\CoreBundle\Event\SitemapEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class SitemapListener
{
    public function __invoke(SitemapEvent $objEvent): void
    {

        if (!\method_exists($objEvent, 'addUrlToDefaultUrlSet')) {
            return;
        }

        foreach ($objEvent->getRootPageIds() as $strRootId) {

            $objPage = PageModel::findByPk($strRootId);

            foreach ((new GetSearchablePagesListener())->getSearchablePagesByPagesRoles([], $strRootId, true, $objPage->language) as $strPage) {
                $objEvent->addUrlToDefaultUrlSet($strPage);
            }

            foreach ((new GetSearchablePagesListener())->getSearchablePages([], $strRootId, true, $objPage->language) as $strPage) {
                $objEvent->addUrlToDefaultUrlSet($strPage);
            }
        }
    }
}