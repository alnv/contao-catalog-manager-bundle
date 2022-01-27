<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class Watchlist {

    public static function getForm($strIdentifier, $strTable, $arrOptions=[]) {

        $objTemplate = new \FrontendTemplate('ce_watchlist_form');
        $arrData = self::getWatchlistData($strIdentifier, $strTable);

        $objTemplate->setData([
            'data' => base64_encode($strIdentifier.':'.$strTable),
            'units' => $arrData['units'],
            'added' => isset($arrData['id']),
            'useUnits' => (bool) $arrOptions['useUnits'],
            'buttonAddLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'],
            'buttonRemoveLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'],
            'buttonUnitsLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListUnitsButtonLabel'],
        ]);

        return $objTemplate->parse();
    }

    public static function getData($strIdentifier, $strTable) {

        $arrData = self::getWatchlistData($strIdentifier, $strTable);

        return isset($arrData['id']) ? $arrData : [];
    }

    public static function getWatchlistData($strIdentifier, $strTable) {

        $objWatchlist = \Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel::getByIdentifierAndTable($strIdentifier, $strTable);

        if (!$objWatchlist) {
            return [
                'units' => 1
            ];
        }

        return $objWatchlist->row();
    }

    public static function updateWatchlist($strIdentifier, $strTable, $strUnits) {

        $objItem = \Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel::getByIdentifierAndTable($strIdentifier, $strTable);

        if (!$objItem) {
            $objItem = new \Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel();
            $objItem->table = $strTable;
            $objItem->created_at = time();
            $objItem->identifier = $strIdentifier;
            $objItem->session = self::getSessionId();
            $objItem->member = \FrontendUser::getInstance()->id ?: 0;
        }

        $objItem->tstamp = time();
        $objItem->units = $strUnits;

        if (!$strUnits) {
            $objItem->delete();
            return [
                'units' => 1
            ];
        }

        return $objItem->save()->row();
    }

    public static function getSessionId() {

        return \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId();
        /*
        $objSession = \System::getContainer()->get('session');
        $strSessionId = $objSession->get('watchlist-session');

        if (!$strSessionId) {
            $strSessionId = substr(md5(uniqid() . '.' . time()), 0, 64);
            $objSession->set('watchlist-session', $strSessionId);
        }

        return $strSessionId;
        */
    }
}