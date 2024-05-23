<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Inserttags\MasterInsertTag;
use Alnv\ContaoCatalogManagerBundle\Inserttags\WatchlistInsertTag;
use Alnv\ContaoCatalogManagerBundle\Inserttags\PageInsertTag;
use Alnv\ContaoCatalogManagerBundle\Inserttags\Inserttags;
use Alnv\ContaoCatalogManagerBundle\Inserttags\ActiveInsertTag;
use Contao\Controller;

class ReplaceInsertTagsListener
{

    public function __invoke(string $insertTag, bool $useCache, string $cachedValue, array $flags, array $tags, array $cache, int $_rit, int $_cnt)
    {

        $arrFragments = explode('::', $insertTag);
        $strInsertTag = $arrFragments[0] ?? '';

        switch ($strInsertTag)
        {
            case 'ACTIVE':
                return (new ActiveInsertTag())->replace($insertTag);
            case 'ACTIVE_PAGE':
                return (new PageInsertTag())->replace($insertTag);
            case 'WATCHLIST':
            case 'WATCHLIST-TABLE':
            case 'WATCHLIST-RESET':
            case 'WATCHLIST-COUNT':
                return (new WatchlistInsertTag())->replace($insertTag);
            case 'MASTER':
                return (new MasterInsertTag())->replace($insertTag);
            case 'CM-USER':
            case 'TIMESTAMP':
            case 'LAST-ADDED-MASTER-VIEW-IDS':
                return (new Inserttags())->replace($insertTag);
            default:
                return false;
        }
    }
}