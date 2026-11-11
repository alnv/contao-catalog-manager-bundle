<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\WatchlistModel;
use Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit as WidgetToolkit;
use Contao\Combiner;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\System;

class Watchlist
{

    public static function getForm($strIdentifier, $strTable, $arrOptions = []): string
    {

        WidgetToolkit::addVueJsScript();

        $objCombiner = new Combiner();
        $objCombiner->add('bundles/alnvcontaocatalogmanager/js/vue/components/watchlist-form-component.js');
        $GLOBALS['TL_HEAD']['watchlist-form-component'] = '<script src="' . $objCombiner->getCombinedFile() . '"></script>';

        if (!($arrOptions['disableCss'] ?? false)) {
            $objCssCombiner = new Combiner();
            $objCssCombiner->add('bundles/alnvcontaocatalogmanager/css/watchlist.scss');
            $GLOBALS['TL_CSS']['watchlist-form-component'] = $objCssCombiner->getCombinedFile();
        }

        System::loadLanguageFile('default');

        $objTemplate = new FrontendTemplate('ce_watchlist_form');
        $arrData = self::getWatchlistData($strIdentifier, $strTable);

        $objTemplate->setData([
            'data' => base64_encode($strIdentifier . ':' . $strTable),
            'units' => $arrData['units'],
            'added' => isset($arrData['id']),
            'id' => 'id_' . md5($strIdentifier . ':' . $strTable),
            'requestToken' => System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue(),
            'useUnits' => (bool)($arrOptions['useUnits'] ?? false),
            'buttonAddLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'],
            'buttonRemoveLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'],
            'buttonUnitsLabel' => &$GLOBALS['TL_LANG']['MSC']['watchListUnitsButtonLabel'],
            'itemTitle' => (string)($arrOptions['title'] ?? ''),
            'labels' => array_filter(array_merge(
                (array)($GLOBALS['TL_LANG']['MSC']['cmWatchlist'] ?? []),
                // bestehende (ggf. im Projekt überschriebene) Labels haben Vorrang
                [
                    'add' => $GLOBALS['TL_LANG']['MSC']['watchListAddButtonLabel'] ?? null,
                    'remove' => $GLOBALS['TL_LANG']['MSC']['watchListRemoveButtonLabel'] ?? null,
                    'units' => $GLOBALS['TL_LANG']['MSC']['watchListUnitLabel'] ?? null,
                ]
            ), static fn ($v) => \is_string($v) && $v !== ''),
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