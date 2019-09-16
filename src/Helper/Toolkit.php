<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;


class Toolkit {


    public static function parse( $varValue, $strDelimiter = ', ' ) {

        if ( is_array( $varValue ) ) {

            $arrValues = array_map( function ( $arrValue ) {

                return $arrValue['value'];

            }, $varValue );

            return implode( $strDelimiter, $arrValues );
        }

        return $varValue;
    }


    public static function getSqlTypes() {

        // @todo numbers
        return [

            'vc255' => "varchar(255) NOT NULL default '%s'",
            'c1' => "char(1) NOT NULL default ''",
            'i10' => "int(10) unsigned NOT NULL default '0'",
            'iNotNull10' => "int(10) unsigned NULL",
            'text' => "text NULL",
            'longtext' => "longtext NULL",
            'blob' => "blob NULL"
        ];
    }


    public static function getSql( $strType, $arrOptions = [] ) {

        $arrSql = static::getSqlTypes();

        switch ( $strType ) {

            case 'text':

                if ( $arrOptions['multiple'] ) {

                    return $arrSql['blob'];
                }

                return sprintf( $arrSql['vc255'], ( $arrOptions['default'] ? $arrOptions : '' ) );

                break;

            case 'textarea':

                if ( $arrOptions['tinyMce'] ) {

                    return $arrSql['longtext'];
                }

                return $arrSql['text'];

                break;

            case 'select':

                if ( $arrOptions['multiple'] ) {

                    return $arrSql['blob'];
                }

                return $arrSql['vc255'];

                break;

            case 'checkbox':

                if ( !$arrOptions['multiple'] ) {

                    return $arrSql['c1'];
                }

                return $arrSql['blob'];

                break;

            case 'radio':

                return $arrSql['vc255'];

                break;

            case 'upload':

                return $arrSql['blob'];

                break;

            default:

                return $arrSql['blob'];

                break;
        }
    }
}