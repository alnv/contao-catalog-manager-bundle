<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Contao\Model;

class CatalogOptionModel extends Model
{

    protected static $strTable = 'tl_catalog_option';

    public static function findByValueAndPid($strValue, $strPid, array $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.`value`=? AND $table.pid=?"];

        return static::findOneBy($arrColumns, [$strValue, $strPid], $arrOptions);
    }
}