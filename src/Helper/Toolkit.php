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
}