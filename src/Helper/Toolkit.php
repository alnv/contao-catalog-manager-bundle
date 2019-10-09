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

                return sprintf( $arrSql['vc255'], ( $arrOptions['default'] ? $arrOptions : '' ) );

                break;

            case 'checkbox':

                if ( !$arrOptions['multiple'] ) {

                    return $arrSql['c1'];
                }

                return $arrSql['blob'];

                break;

            case 'radio':

                return sprintf( $arrSql['vc255'], ( $arrOptions['default'] ? $arrOptions : '' ) );

                break;

            case 'upload':

                return $arrSql['blob'];

                break;

            default:

                return $arrSql['blob'];

                break;
        }
    }


    public static function parseDetailLink( $varPage, $strAlias ) {

        $arrPage = null;

        if ( is_numeric( $varPage ) && $varPage ) {

            $objPage = \PageModel::findByPk( $varPage );

            if ( $objPage !== null ) {

                $arrPage = $objPage->row();
            }
        }

        if ( is_array( $varPage ) && !empty( $varPage ) ) {

            $arrPage = $varPage;
        }

        return \Controller::generateFrontendUrl( $arrPage, $strAlias ? '/' . $strAlias : '' );
    }


    public static function parseParametersFromString( $strParameter ) {

        $arrChunks = explode('?', urldecode( $strParameter ), 2 );
        $strSource = \StringUtil::decodeEntities( $arrChunks[1] );
        $strSource = str_replace( '[&]', '&', $strSource );

        return explode( '&', $strSource );
    }


    public static function getValueFromUrl( $arrValue ) {

        if ( $arrValue == '' || $arrValue == null ) {

            return '';
        }

        if ( is_array( $arrValue ) ) {

            return implode( ',', $arrValue );
        }

        return $arrValue;
    }


    public static function getOrderByStatementFromArray( $arrOrder ) {

        return implode(',', array_filter( array_map( function ( $arrOrder ) {

            if ( !isset( $arrOrder['field'] ) || !$arrOrder['field'] ) {

                return '';
            }

            if ( !$arrOrder['order'] ) {

                $arrOrder['order'] = 'ASC';
            }

            return $arrOrder['field'] . ' ' . $arrOrder['order'];

            }, $arrOrder ) ) );
    }
}