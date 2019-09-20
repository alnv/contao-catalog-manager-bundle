<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;


class DynModel extends \Model {


    public static $strTable = '';


    public function __construct( $objResult = null ) {

        if ( !static::$strTable ) {

            return null;
        }

        parent::__construct( $objResult );
    }


    public function createDynTable( $strTable, $objResult = null ) {

        static::$strTable = $strTable;
        static::$arrClassNames[ $strTable ] = 'Alnv\ContaoCatalogManagerBundle\Models\DynModel';
        parent::__construct( $objResult );
    }
}