<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class WatchlistModel extends \Model {

    protected static $strTable = 'tl_watchlist';

    public static function getByIdentifierAndTable($strIdentifier, $strTablename, array $arrOptions=[]) {

        $strTable = static::$strTable;
        $arrIdentifiers = ["$strTable.session=?"];

        $arrColumns = ["$strTable.identifier=? AND $strTable.table=? AND $strTable.sent!=?"];
        $arrValues = [$strIdentifier, $strTablename, '1', \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getSessionId()];

        if (FE_USER_LOGGED_IN) {
            $arrIdentifiers[] = "$strTable.member=?";
            $arrValues[] = \FrontendUser::getInstance()->id;
        }

        $arrColumns[] = '('.implode(' OR ', $arrIdentifiers).')';

        return static::findOneBy($arrColumns, $arrValues, $arrOptions);
    }

    public static function getBySession($arrOptions=[]) {

        $strTable = static::$strTable;

        $arrIdentifiers = ["$strTable.session=?"];
        $arrOptions['column'] = ["$strTable.sent!=?"];
        $arrOptions['value'] = ['1', \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getSessionId()];

        if (FE_USER_LOGGED_IN) {
            $arrIdentifiers[] = "$strTable.member=?";
            $arrOptions['value'][] = \FrontendUser::getInstance()->id;
        }

        $arrOptions['column'][] = '('.implode(' OR ', $arrIdentifiers).')';
        $arrOptions['order'] = "$strTable.created_at DESC";

        return static::findAll($arrOptions);
    }
}