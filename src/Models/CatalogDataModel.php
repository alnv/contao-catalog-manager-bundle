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

        $table = static::$strTable;
        $arrColumns = ["$table.`type`=? AND $table.`table`=? AND $table.`identifier`=? AND ($table.`session`=? OR $table.`member`=?)"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, Toolkit::getSessionId(), (FrontendUser::getInstance()->id ?: 0)], $arrOptions);
    }

    public static function getLastAddedByType($strType, $arrOptions = [])
    {

        $table = static::$strTable;
        $arrOptions = [
            'value' => [$strType, Toolkit::getSessionId(), (FrontendUser::getInstance()->id ?: 0)],
            'column' => ["$table.`type`=? AND ($table.`session`=? OR $table.`member`=?)"],
            'order' => "$table.tstamp DESC",
            'limit' => $arrOptions['limit']
        ];

        return static::findAll($arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndDayPeriod($strType, $strTable, $strIdentifier, $intDayPeriod, $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.`type`=? AND $table.`table`=? AND $table.`identifier`=? AND $table.`day`=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intDayPeriod], $arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndMonthPeriod($strType, $strTable, $strIdentifier, $intMonthPeriod, $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.type=? AND $table.`table`=? AND $table.identifier=? AND $table.month=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intMonthPeriod], $arrOptions);
    }

    public static function getByTypeAndTableIdentifierAndYearPeriod($strType, $strTable, $strIdentifier, $intYearPeriod, $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.type=? AND $table.`table`=? AND $table.identifier=? AND $table.year=?"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, $intYearPeriod], $arrOptions);
    }
}