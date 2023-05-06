<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\FrontendUser;
use Contao\Model;

class CatalogDataModel extends Model
{

    protected static $strTable = 'tl_catalog_data';

    public static function getByTypeAndTableAndIdentifier($strType, $strTable, $strIdentifier, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.type=? AND $strT.table=? AND $strT.identifier=? AND ($strT.session=? OR $strT.member=?)"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, Toolkit::getSessionId(), (FrontendUser::getInstance()->id ?: 0)], $arrOptions);
    }

    public static function getLastAddedByType($strType, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrOptions = [
            'value' => [$strType, Toolkit::getSessionId(), (FrontendUser::getInstance()->id ?: 0)],
            'column' => ["$strT.type=? AND ($strT.session=? OR $strT.member=?)"],
            'order' => "$strT.tstamp DESC",
            'limit' => $arrOptions['limit']
        ];

        return static::findAll($arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndDayPeriod($strType, $strTable, $strIdentifier, $intDayPeriod, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.type=? AND $strT.table=? AND $strT.identifier=? AND $strT.day=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intDayPeriod], $arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndMonthPeriod($strType, $strTable, $strIdentifier, $intMonthPeriod, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.type=? AND $strT.table=? AND $strT.identifier=? AND $strT.month=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intMonthPeriod], $arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndYearPeriod($strType, $strTable, $strIdentifier, $intYearPeriod, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.type=? AND $strT.table=? AND $strT.identifier=? AND $strT.year=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intYearPeriod], $arrOptions);
    }
}