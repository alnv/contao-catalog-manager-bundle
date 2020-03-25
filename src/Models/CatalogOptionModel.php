<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogOptionModel extends \Model {

    protected static $strTable = 'tl_catalog_option';

    public static function findByValueAndPid( $strValue, $strPid, array $arrOptions=[] ) {

        $strT = static::$strTable;
        $arrColumns = [ "$strT.value=? AND $strT.pid=?" ];

        return static::findOneBy( $arrColumns, [$strValue, $strPid], $arrOptions );
    }
}