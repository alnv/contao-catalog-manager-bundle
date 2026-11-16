<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Contao\Model;

class CatalogFieldModel extends Model
{

    protected static $strTable = 'tl_catalog_field';

    public static function findByFieldname($strFieldname, array $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.`fieldname`=?"];

        return static::findOneBy($arrColumns, $strFieldname, $arrOptions);
    }

    public static function findByFieldnameAndPid($strFieldname, $strId, array $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.`fieldname`=?", "$table.`pid`=?"];

        return static::findOneBy($arrColumns, [$strFieldname, $strId], $arrOptions);
    }

    public static function findByParent($strId)
    {

        $table = static::$strTable;

        $arrOptions = [
            'column' => ["$table.pid=?"],
            'value' => [$strId],
            'order' => "$table.`sorting`"
        ];

        return static::findAll($arrOptions);
    }
}