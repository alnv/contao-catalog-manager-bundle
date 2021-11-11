<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class WatchlistModel extends \Model {

    protected static $strTable = 'tl_watchlist';

    public static function getByIdentifierAndTable($strIdentifier, $strTablename, array $arrOptions=[]) {

        $strTable = static::$strTable;
        $arrColumns = ["$strTable.identifier=? AND $strTable.table=? AND $strTable.sent!=? AND $strTable.session=?"];

        return static::findOneBy($arrColumns, [$strIdentifier, $strTablename, '1', \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getSessionId()], $arrOptions);
    }

    public static function getBySession($arrOptions=[]) {

        $strTable = static::$strTable;

        $arrOptions['column'] = ["$strTable.sent!=? AND $strTable.session=?"];;
        $arrOptions['value'] = ['1', \Alnv\ContaoCatalogManagerBundle\Library\Watchlist::getSessionId()];
        $arrOptions['order'] = "$strTable.created_at DESC";

        return static::findAll($arrOptions);
    }
}