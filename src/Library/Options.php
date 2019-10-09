<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;


class Options {


    protected static $arrField = [];
    protected static $arrInstances = [];
    protected static $arrDataContainer = null;


    public static function getInstance( $strInstanceId ) {

        if ( !array_key_exists( $strInstanceId, self::$arrInstances ) ) {

            self::$arrInstances[ $strInstanceId ] = new self;
        }

        return self::$arrInstances[ $strInstanceId ];
    }


    public static function getOptions() {

        $arrReturn = [];

        switch ( static::$arrField['optionsSource'] ) {

            case 'options':

                $objOptions = CatalogOptionModel::findAll([
                    'column' => [ 'pid=?' ],
                    'value' => [ static::$arrField['id'] ],
                    'order' => 'sorting ASC'
                ]);

                if ( $objOptions === null ) {

                    return $arrReturn;
                }

                while ( $objOptions->next() ) {

                    $arrReturn[ $objOptions->value ] = $objOptions->label;
                }

                break;

            case 'dbOptions':

                return $arrReturn;

                break;


            default:

                return $arrReturn;

                break;
        }

        return $arrReturn;
    }


    public static function setParameter( $arrField, $objDataContainer = null ) {

        static::$arrField = $arrField;
        static::$arrDataContainer = $objDataContainer;
    }
}