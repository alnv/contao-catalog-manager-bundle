<?php

namespace Alnv\ContaoCatalogManagerBundle\Inserttags;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Watchlist;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Contao\Database;
use Contao\DataContainer;
use Contao\FrontendTemplate;
use Contao\System;

class WatchlistInsertTag
{

    public function replace($strFragment)
    {

        $arrFragments = explode('::', $strFragment);

        $arrOptions = [];
        $strType = $arrFragments[0] ?? '';
        $strType = strtoupper($strType);
        $strParams = $arrFragments[1] ?? '';

        if (!in_array($strType, ['WATCHLIST', 'WATCHLIST-TABLE', 'WATCHLIST-RESET', 'WATCHLIST-COUNT'])) {
            return false;
        }

        if (strpos($strParams, '?') !== false) {
            $arrParams = Toolkit::parseParametersFromString($arrFragments[1]);
            foreach ($arrParams as $strParam) {
                list($strKey, $strOption) = explode('=', $strParam);
                switch ($strKey) {
                    case 'tables':
                        $arrOptions['tables'] = explode(',', $strOption);
                        break;
                    case 'total':
                        $arrOptions['total'] = (bool)$strOption;
                        break;
                    case 'template':
                        $arrOptions['template'] = $strOption ?: '';
                        break;
                }
            }
        }

        if ($strType == 'WATCHLIST') {
            return $this->getWatchListIDs($arrOptions);
        }

        if ($strType == 'WATCHLIST-COUNT') {

            $intCount = 0;
            $objWatchlist = WatchlistModel::getBySession();

            if (!$objWatchlist) {
                return $intCount;
            }

            while ($objWatchlist->next()) {
                if (!Database::getInstance()->tableExists($objWatchlist->table)) {
                    continue;
                }
                $objEntity = Database::getInstance()->prepare('SELECT * FROM ' . $objWatchlist->table . ' WHERE id=?')->limit(1)->execute($objWatchlist->identifier);
                if (!$objEntity->numRows) {
                    continue;
                }
                if ($arrOptions['total']) {
                    $intCount += (int)$objWatchlist->units;
                } else {
                    $intCount++;
                }
            }

            return $intCount;
        }

        if ($strType == 'WATCHLIST-TABLE') {

            $arrItems = [];
            $arrIdentifiers = [];
            $objTemplate = new FrontendTemplate(($arrOptions['template'] ?: 'ce_watchlist_table'));
            $objWatchlist = WatchlistModel::getBySession();

            if ($objWatchlist) {
                while ($objWatchlist->next()) {

                    if (!empty($arrOptions['tables']) && !in_array($objWatchlist->table, $arrOptions['tables'])) {
                        continue;
                    }

                    if (!isset($arrIdentifiers[$objWatchlist->table])) {
                        $arrIdentifiers[$objWatchlist->table] = [];
                    }

                    $arrIdentifiers[$objWatchlist->table][] = $objWatchlist->identifier;
                }
            }

            foreach ($arrIdentifiers as $strTable => $arrIds) {

                if (empty($arrIds)) {
                    continue;
                }

                DataContainer::loadDataContainer($strTable);
                System::loadLanguageFile($strTable);

                $strT = $GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?: $strTable;

                $arrValue = [];
                $arrColumn = [];
                $arrQuery = [];

                foreach ($arrIds as $strId) {
                    $arrQuery[] = "$strT.id=?";
                    $arrValue[] = $strId;
                }
                $arrColumn[] = implode(' OR ', $arrQuery);

                $objListing = new Listing($strTable, [
                    'value' => $arrValue,
                    'column' => $arrColumn,
                    'order' => "FIELD($strT.id," . implode(',', $arrIds) . ")"
                ]);

                if (!isset($arrItems[$strTable])) {

                    $objCatalog = CatalogModel::findByTableOrModule($strTable);
                    $arrItems[$strTable] = [
                        'entities' => [],
                        'catalog' => $objCatalog ? $objCatalog->row() : [],
                        'unitLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListUnitLabel'],
                        'titleLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListTitleLabel'],
                    ];
                }

                foreach ($objListing->parse() as $arrEntity) {
                    $arrEntity['watchlistData'] = Watchlist::getData($arrEntity['id'], $strTable);
                    $arrItems[$strTable]['entities'][] = $arrEntity;
                }
            }


            $objTemplate->setData([
                'items' => $arrItems
            ]);

            return $objTemplate->parse();
        }

        if ($strType == 'WATCHLIST-RESET') {

            $objWatchlist = WatchlistModel::getBySession();

            if (!$objWatchlist) {
                return '';
            }

            while ($objWatchlist->next()) {

                $objWatchlist->tstamp = time();
                $objWatchlist->sent = '1';
                $objWatchlist->save();
            }

            return '';
        }

        return false;
    }

    protected function getWatchListIDs($arrOptions = [])
    {

        $arrIds = [];
        $objWatchlist = WatchlistModel::getBySession();

        if (!$objWatchlist) {
            return '0';
        }

        while ($objWatchlist->next()) {
            if (!empty($arrOptions['tables']) && !in_array($objWatchlist->table, $arrOptions['tables'])) {
                continue;
            }
            if (!in_array($objWatchlist->identifier, $arrIds)) {
                $arrIds[] = $objWatchlist->identifier;
            }
        }

        $arrIds = array_filter($arrIds);

        return empty($arrIds) ? '0' : serialize($arrIds);
    }

    public function __invoke($insertTag)
    {
        return $this->replace($insertTag);
    }
}