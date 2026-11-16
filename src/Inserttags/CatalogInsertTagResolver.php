<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;

/**
 * Registriert die Insert-Tags des Catalog Managers beim Insert-Tag-Parser von Contao (statt über den
 * veralteten Hook replaceInsertTags). Die Auflösung selbst bleibt in den bisherigen Klassen; sie erhalten
 * den Tag wie bisher als "name::parameter::…" (Parameter bereits aufgelöst).
 *
 * Tags mit Bindestrich (watchlist-table, watchlist-reset, watchlist-count, cm-user,
 * last-added-master-view-ids) erlaubt der Parser nicht als Namen; sie laufen weiter über den Hook.
 */
class CatalogInsertTagResolver
{
    #[AsInsertTag('active')]
    public function active(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return $this->delegate(new ActiveInsertTag(), $insertTag);
    }

    #[AsInsertTag('active_page')]
    public function activePage(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return $this->delegate(new PageInsertTag(), $insertTag);
    }

    #[AsInsertTag('master')]
    public function master(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return $this->delegate(new MasterInsertTag(), $insertTag);
    }

    #[AsInsertTag('watchlist')]
    public function watchlist(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return $this->delegate(new WatchlistInsertTag(), $insertTag);
    }

    #[AsInsertTag('timestamp')]
    public function timestamp(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return $this->delegate(new Inserttags(), $insertTag);
    }

    private function delegate(object $objResolver, ResolvedInsertTag $insertTag): InsertTagResult
    {
        $strTag = $insertTag->getName() . $insertTag->getParameters()->serialize();
        $varValue = $objResolver->replace($strTag);

        if ($varValue === false) {
            return new InsertTagResult('{{' . $strTag . '}}', OutputType::text);
        }

        return new InsertTagResult((string)$varValue, OutputType::html);
    }
}
