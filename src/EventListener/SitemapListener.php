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

        $arrPages = [];

        foreach ($objEvent->getRootPageIds() as $strRootId) {

            $objPage = PageModel::findByPk($strRootId);

            foreach ((new GetSearchablePagesListener())->getSearchablePagesByPagesRoles([], $strRootId, true, $objPage->language) as $strPage) {
                $arrPages[] = $strPage;
            }

            foreach ((new GetSearchablePagesListener())->getSearchablePages([], $strRootId, true, $objPage->language) as $strPage) {
                $arrPages[] = $strPage;
            }
        }

        $arrPages = \array_values(\array_unique($arrPages));

        foreach ($arrPages as $strPage) {
            $objEvent->addUrlToDefaultUrlSet($strPage);
        }
    }
}