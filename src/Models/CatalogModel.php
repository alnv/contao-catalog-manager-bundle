<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;


class CatalogModel extends \Model {


    protected static $strTable = 'tl_catalog';


    public static function findByTableOrModule( $strIdentifier, array $arrOptions=[] ) {

        $strT = static::$strTable;
        $arrColumns = [ "$strT.table=? OR $strT.module=?" ];

        return static::findOneBy( $arrColumns, [ $strIdentifier, $strIdentifier ], $arrOptions );
    }
}