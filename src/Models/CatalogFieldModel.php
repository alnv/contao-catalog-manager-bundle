<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;


class CatalogFieldModel extends \Model {


    protected static $strTable = 'tl_catalog_field';


    public static function findByFieldname( $strFieldname, array $arrOptions=[] ) {

        $strT = static::$strTable;
        $arrColumns = [ "$strT.fieldname=?" ];

        return static::findOneBy( $arrColumns, $strFieldname, $arrOptions );
    }
}