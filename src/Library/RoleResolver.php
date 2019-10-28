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
                'type' => $arrField['inputType']
            ];
        }

        return $arrRoles;
    }


    public function getGeoCodingAddress() {

        $objAddress = new \Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder();

        if ( isset( self::$arrRoles[ 'street' ][ 'name' ] ) ) {

            $objAddress->setStreet( self::$arrEntity[ self::$arrRoles[ 'street' ]['name'] ] );
        }

        if ( isset( self::$arrRoles[ 'streetNumber' ][ 'name' ] ) ) {

            $objAddress->setStreetNumber( self::$arrEntity[ self::$arrRoles[ 'streetNumber' ]['name'] ] );
        }

        if ( isset( self::$arrRoles[ 'postal' ][ 'name' ] ) ) {

            $objAddress->setZip( self::$arrEntity[ self::$arrRoles[ 'postal' ]['name'] ] );
        }

        if ( isset( self::$arrRoles[ 'city' ][ 'name' ] ) ) {

            $objAddress->setCity( self::$arrEntity[ self::$arrRoles[ 'city' ]['name'] ] );
        }

        if ( isset( self::$arrRoles[ 'state' ][ 'name' ] ) ) {

            $objAddress->setState( self::$arrEntity[ self::$arrRoles[ 'state' ]['name'] ] );
        }

        if ( isset( self::$arrRoles[ 'country' ][ 'name' ] ) ) {

            $objAddress->setCountry( self::$arrEntity[ self::$arrRoles[ 'country' ]['name'] ] );
        }

        return $objAddress->getAddress();
    }


    public function getGeoCodingValues() {

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