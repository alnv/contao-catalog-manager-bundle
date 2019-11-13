<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;


class Toolkit {


    public static function parse( $varValue, $strDelimiter = ', ', $strField = 'label' ) {

        if ( is_array( $varValue ) ) {

            $arrValues = array_map( function ( $arrValue ) use ( $strField ) {

                return $arrValue[ $strField ];

            }, $varValue );

            return implode( $strDelimiter, $arrValues );
        }

        return $varValue;
    }


    public static function getSqlTypes() {

        // @todo numbers

        return [

            'vc255' => "varchar(255) NOT NULL default '%s'",
            'vc8' => "varchar(8) NOT NULL default '%s'",
            'c1' => "char(1) NOT NULL default ''",
            'i10' => "int(10) unsigned NOT NULL default '0'",
            'i10NullAble' => "int(10) unsigned NULL",
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

            case 'color':

                return sprintf( $arrSql['vc8'], ( $arrOptions['default'] ? $arrOptions : '' ) );

                break;

            case 'date':

                return sprintf( $arrSql['i10NullAble'], ( $arrOptions['default'] ? $arrOptions : '' ) );

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


    public static function renderRow( $arrRow, $arrLabelFields, $arrCatalog, $arrFields ) {

        $arrColumns = [];

        foreach ( $arrLabelFields as $strField ) {

            $arrColumns[ $strField ] = static::parseCatalogValue( $arrRow[ $strField ], \Widget::getAttributesFromDca( $arrFields[ $strField ], $strField, $arrRow[ $strField ], $strField, $arrCatalog['table'] ), $arrRow, true );
        }

        if ( count( $arrColumns ) < 2 ) {

            return array_values( $arrColumns )[0];
        }

        $intIndex = -1;
        $arrLabels = [];
        $strTemplate = '<div class="tl_content_left">';

        foreach ( $arrColumns as $strField => $strValue ) {
            $intIndex += 1;
            if ( !$intIndex ) {
                $strTemplate .= $strValue;
                continue;
            }
            $arrLabels[] = strtoupper( $strField ) . ': ' . $strValue;
        }

        $strTemplate .= '<span style="color:#999;padding-left:3px">('. implode( $arrLabels, ', ' ) .')</span>' . '</div>';

        return $strTemplate;
    }


    public static function renderTreeRow( $arrRow, $strLabel, $arrLabelFields, $arrCatalog, $arrFields ) {

        $intIndex = 0;
        $arrColumns = [];
        $strTemplate = '';
        $strImage = 'articles';

        foreach ( $arrLabelFields as $strField ) {

            $arrColumns[ $strField ] = static::parseCatalogValue( $arrRow[ $strField ], \Widget::getAttributesFromDca( $arrFields[ $strField ], $strField, $arrRow[ $strField ], $strField, $arrCatalog['table'] ), $arrRow, true );
        }

        if ( count( $arrColumns ) < 2 ) {

            return array_values( $arrColumns )[0];
        }

        foreach ( $arrColumns as $strField => $strValue ) {

            $strTemplate .= !$intIndex ? $strValue :  ( ' <span class="'. $strField .'" style="color:#999;padding-left:3px">' . ( $intIndex === 1 ? '[' : '' ) . $strValue . ( $intIndex === count( $arrColumns ) - 1 ? ']' : '' ) . '</span>' );
            $intIndex += 1;
        }

        return \Image::getHtml( $strImage . '.svg', '', '') . ' ' . $strTemplate;
    }


    public static function parseCatalogValue( $varValue, $arrField, $arrValues = [], $blnStringFormat = false ) {

        if ( $varValue === '' || $varValue === null ) {

            return $varValue;
        }

        if ( !isset( $arrField['type'] ) ) {

            return $varValue;
        }

        switch ( $arrField['type'] ) {

            case 'text':

                return $arrField['value'];

                break;

            case 'checkbox':
            case 'select':
            case 'radio':

                $varValue = !is_array( $arrField['value'] ) ? [ $arrField['value'] ] : $arrField['value'];
                $arrOptionValues =  static::getSelectedOptions( $varValue, $arrField['options'] );

                if ( $blnStringFormat ) {

                    return static::parse( $arrOptionValues );
                }

                return $arrOptionValues;

                break;

            case 'fileTree':

                $strSizeId = null;

                if ( isset( $arrField['imageSize'] ) && $arrField['imageSize'] ) {

                    $strSizeId = $arrField['imageSize'];
                }

                if ( isset( $arrField['isImage'] ) && $arrField['isImage'] == true ) {

                    return Image::getImage( $varValue, $strSizeId );
                }

                return []; // @todo files

                break;

            case 'pageTree':

                return ''; // @todo parse url

                break;
        }

        return $arrField['value'];
    }


    public static function getSelectedOptions( $arrValues, $arrOptions ) {

        $arrReturn = [];

        if ( !is_array( $arrOptions ) || !is_array( $arrValues ) ) {

            return [];
        }

        foreach ( $arrOptions as $arrValue ) {

            if ( in_array( $arrValue['value'], $arrValues ) ) {

                $arrReturn[] = $arrValue;
            }
        }

        return $arrReturn;
    }


    public static function saveGeoCoordinates( $strTable, $arrActiveRecord ) {

        $arrEntity = [];

        if ( !$arrActiveRecord['id'] ) {

            return null;
        }

        foreach ( $arrActiveRecord as $strField => $strValue ) {

            $arrEntity[ $strField ] = static::parseCatalogValue( $strValue, \Widget::getAttributesFromDca( $GLOBALS['TL_DCA'][ $strTable ]['fields'][ $strField ], $strField, $strValue, $strField, $strTable ), $arrActiveRecord, true );
        }

        $objRoleResolver = RoleResolver::getInstance( $strTable, $arrEntity );
        $arrGeoFields = $objRoleResolver->getGeoCodingFields();
        $strAddress = $objRoleResolver->getGeoCodingAddress();

        $objDatabase = \Database::getInstance();
        $objGeoCoding = new \Alnv\ContaoGeoCodingBundle\Library\GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress( 'google-geocoding', $strAddress );

        if ( ( $arrEntity[ $arrGeoFields['longitude'] ] !== null && $arrEntity[ $arrGeoFields['longitude'] ]  !== '' ) && ( $arrEntity[ $arrGeoFields['latitude'] ] !== null && $arrEntity[ $arrGeoFields['latitude'] ] !== '' ) ) {

            return null;
        }

        if ( $arrGeoCoding !== null && !empty( $arrGeoFields ) ) {

            $arrSet = [];
            $arrSet[ 'tstamp' ] = time();
            $arrSet[ $arrGeoFields['longitude'] ] = $arrGeoCoding['longitude'];
            $arrSet[ $arrGeoFields['latitude'] ] = $arrGeoCoding['latitude'];

            $objDatabase->prepare( 'UPDATE '. $strTable .' %s WHERE id = ?' )->set( $arrSet )->execute( $arrEntity['id'] );
        }
    }


    public function saveAlias( $arrActiveRecord, $arrFields, $arrCatalog ) {

        if ( !$arrActiveRecord['id'] ) {

            return null;
        }

        $arrValues = [];
        $strAlias = $arrActiveRecord['alias'];
        $objDatabase = \Database::getInstance();

        /* @todo only if alias field exist in palette
        if ( $strAlias !== '' && $strAlias !== null && \Validator::isAlias( $strAlias ) && !$objDatabase->prepare('SELECT * FROM ' . $arrCatalog['table'] . ' WHERE `alias`=? AND `pid`=? AND `id`!=?' )->limit(1)->execute( $strAlias, $arrActiveRecord['pid'], $arrActiveRecord['id'] )->numRows ) {

            return null;
        }
        */

        foreach ( $arrFields as $strFieldname => $arrField ) {

            if ( !isset( $arrField['eval'] ) ) {

                continue;
            }

            if ( !$arrField['eval']['useAsAlias'] ) {

                continue;
            }

            if ( isset( $arrActiveRecord[ $strFieldname ] ) && $arrActiveRecord[ $strFieldname ] !== '' && $arrActiveRecord[ $strFieldname ] !== null ) {

                $arrValues[] = $arrActiveRecord[ $strFieldname ];
            }
        }

        if ( empty( $arrValues ) ) {

            $strAlias = md5( time() . '/' . ( $arrActiveRecord['id'] ?: '' ) );

        } else {

            $strAlias = implode( '-', $arrValues );
        }

        $strAlias = \System::getContainer()->get('contao.slug.generator')->generate( \StringUtil::prepareSlug( $strAlias ), []);

        if ( strlen( $strAlias ) > 100 ) {

            $strAlias = substr( $strAlias, 0, 100 );
        }

        if ( $objDatabase->prepare('SELECT * FROM ' . $arrCatalog['table'] . ' WHERE `alias`=? AND `pid`=? AND `id`!=?' )->limit(1)->execute( $strAlias, $arrActiveRecord['pid'], $arrActiveRecord['id'] )->numRows ) {

            $strAlias = $strAlias . '-' . $arrActiveRecord['id'];
        }

        $arrSet = [];
        $arrSet[ 'tstamp' ] = time();
        $arrSet[ 'alias' ] = $strAlias;
        $objDatabase->prepare( 'UPDATE '. $arrCatalog['table'] .' %s WHERE id = ?' )->set( $arrSet )->execute( $arrActiveRecord['id'] );
    }
}