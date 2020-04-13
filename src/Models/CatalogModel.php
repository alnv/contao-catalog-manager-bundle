<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogModel extends \Model {

    protected static $strTable = 'tl_catalog';

    public static function findByTableOrModule( $strIdentifier, array $arrOptions=[] ) {

        $strT = static::$strTable;
        $arrColumns = [ "$strT.table=? OR $strT.module=? OR $strT.id=?" ];

        return static::findOneBy( $arrColumns, [ $strIdentifier, $strIdentifier, (int) $strIdentifier ], $arrOptions );
    }

    public static function findChildrenCatalogsById( $strId ) {

        $strT = static::$strTable;
        $objChildTables = \Database::getInstance()
            ->prepare('SELECT * FROM ' . $strT . ' WHERE pid = ?' )
            ->execute( $strId );

        if ( $objChildTables->numRows < 1 ) {

            return null;
        }

        return static::createCollectionFromDbResult( $objChildTables, 'tl_catalog' );
    }
}