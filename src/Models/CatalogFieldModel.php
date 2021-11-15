<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogFieldModel extends \Model {

    protected static $strTable = 'tl_catalog_field';

    public static function findByFieldname($strFieldname, array $arrOptions=[]) {

        $strT = static::$strTable;
        $arrColumns = ["$strT.fieldname=?"];

        return static::findOneBy($arrColumns, $strFieldname, $arrOptions);
    }

    public static function findByFieldnameAndPid($strFieldname, $strId, array $arrOptions=[]) {

        $strT = static::$strTable;
        $arrColumns = ["$strT.fieldname=?", "$strT.pid=?"];

        return static::findOneBy($arrColumns, [$strFieldname, $strId], $arrOptions);
    }

    public static function findByParent($strId) {

        $strT = static::$strTable;
        $arrOptions = [
            'column' => ["$strT.pid=?"],
            'value' => [$strId],
            'order' => "$strT.sorting"
        ];

        return static::findAll($arrOptions);
    }
}