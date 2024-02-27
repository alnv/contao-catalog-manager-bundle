<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit as WidgetToolkit;
use Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel;
use Contao\Combiner;
use Contao\FrontendTemplate;
use Contao\FrontendUser;

class Watchlist
{

    public static function getForm($strIdentifier, $strTable, $arrOptions = []): string
    {

        WidgetToolkit::addVueJsScript();

        $objCombiner = new Combiner();
        $objCombiner->add('bundles/alnvcontaocatalogmanager/js/vue/components/watchlist-form-component.js');
        $GLOBALS['TL_HEAD']['watchlist-form-component'] = '<script src="'. $objCombiner->getCombinedFile() .'"></script>';

        $objTemplate = new FrontendTemplate('ce_watchlist_form');
        $arrData = self::getWatchlistData($strIdentifier, $strTable);

        $objTemplate->setData([
            'data' => base64_encode($strIdentifier . ':' . $strTable),
            'units' => $arrData['units'],
            'added' => isset($arrData['id']),
            'id' => 'id_' . md5($strIdentifier . ':' . $strTable),
            'useUnits' => (bool)($arrOptions['useUnits']??false),
            'buttonAddLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'],
            'buttonRemoveLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'],
            'buttonUnitsLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListUnitsButtonLabel'],
        ]);

        return $objTemplate->parse();
    }

    public static function getData($strIdentifier, $strTable): array
    {

        $arrData = self::getWatchlistData($strIdentifier, $strTable);

        return isset($arrData['id']) ? $arrData : [];
    }

    public static function getWatchlistData($strIdentifier, $strTable): array
    {

        $objWatchlist = WatchlistModel::getByIdentifierAndTable($strIdentifier, $strTable);

        if (!$objWatchlist) {
            return [
                'units' => 1
            ];
        }

        return $objWatchlist->row();
    }

    public static function updateWatchlist($strIdentifier, $strTable, $strUnits): array
    {

        $objItem = WatchlistModel::getByIdentifierAndTable($strIdentifier, $strTable);

        if (!$objItem) {
            $objItem = new WatchlistModel();
            $objItem->table = $strTable;
            $objItem->created_at = time();
            $objItem->identifier = $strIdentifier;
            $objItem->session = self::getSessionId();
            $objItem->member = FrontendUser::getInstance()->id ?: 0;
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

    public static function getSessionId()
    {

        return Toolkit::getSessionId();
    }
}