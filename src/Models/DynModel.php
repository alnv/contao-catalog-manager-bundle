<?php

namespace Alnv\ContaoCatalogManagerBundle\Model;


class DynModel extends \Model {


    public static $strTable = '';


    public function __construct( $objResult=null ) {

        return null;
    }


    public function createDynTable( $strTable, $objResult=null ) {

        static::$strTable = $strTable;

        parent::__construct( $objResult );
    }
}