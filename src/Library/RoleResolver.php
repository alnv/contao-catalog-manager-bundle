<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class RoleResolver {


    protected static $strTable = null;
    protected static $arrRoles = null;
    protected static $arrEntity = null;
    protected static $arrInstances = [];


    public static function getInstance( $strTable, $arrEntity = [] ) {

        self::$strTable = $strTable;

        if ( !array_key_exists( self::$strTable, self::$arrInstances ) ) {

            self::$arrEntity = $arrEntity;
            self::$arrRoles = static::setRoles();
            self::$arrInstances[ self::$strTable ] = new self;
        }

        return self::$arrInstances[ self::$strTable ];
    }


    protected function setRoles() {

        \Controller::loadDataContainer( self::$strTable );
        \System::loadLanguageFile( self::$strTable );

        $arrRoles = [];
        $arrFields = $GLOBALS['TL_DCA'][ self::$strTable ]['fields'] ?: [];

        foreach ( $arrFields as $strFieldname => $arrField ) {

            if ( !isset( $arrField['eval'] ) ) {

                continue;
            }

            if ( !$arrField['eval']['role'] ) {

                continue;
            }

            $arrRoles[ $arrField['eval']['role'] ] = [

                'name' => $strFieldname,
                'eval' => $arrField['eval'],
                'label' => $arrField['label'],
                'type' => $arrField['inputType'],
                'role' => $GLOBALS['CM_ROLES'][ $arrField['eval']['role'] ]
            ];
        }

        return $arrRoles;
    }


    public function getRole( $strRolename ) {

        return $GLOBALS['CM_ROLES'][ $strRolename ];
    }


    public function getGeoCodingAddress() {

        $arrAddress = [];
        $arrAddressFields = [ 'street', 'streetNumber', 'zip', 'city', 'state', 'country' ];

        foreach ( $arrAddressFields as $strAddressField ) {

            if ( isset( self::$arrEntity[ self::$arrRoles[ $strAddressField ]['name'] ] ) ) {

                $arrAddress[ $strAddressField ] = self::$arrEntity[ self::$arrRoles[ $strAddressField ]['name'] ];
            }
        }

        $objAddress = new \Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder( $arrAddress );

        return $objAddress->getAddress();
    }


    public function getGeoCodingFields() {

        $arrReturn = [];
        $arrGeoRoles = [ 'latitude', 'longitude' ];

        foreach ( $arrGeoRoles as $strRole ) {

            $arrReturn[ $strRole ] = self::$arrRoles[ $strRole ]['name'];
        }

        return $arrReturn;
    }


    protected function getKeyValueByRoles( $arrRoles ) {

        $arrReturn = [];

        foreach ( $arrRoles as $strRole ) {

            if ( !isset( self::$arrRoles[ $strRole ][ 'name' ] ) ) {

                continue;
            }

            $arrReturn[ self::$arrRoles[ $strRole ][ 'name' ] ] = self::$arrEntity[ self::$arrRoles[ $strRole ]['name'] ];
        }

        return $arrReturn;
    }
}