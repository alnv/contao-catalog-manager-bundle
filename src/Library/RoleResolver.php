<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class RoleResolver extends \System {

    public static $strTable = null;
    public static $arrRoles = null;
    public static $arrEntity = null;
    protected static $arrInstances = [];

    public static function getInstance( $strTable, $arrEntity = [] ) {

        if ( $strTable === null ) {
            return new self;
        }

        $strInstanceKey = $strTable . ( $arrEntity['id'] ? '_' . $arrEntity['id'] : '' );

        if ( !array_key_exists( $strInstanceKey, self::$arrInstances ) ) {
            self::$strTable = $strTable;
            self::$arrEntity = $arrEntity;
            self::$arrRoles = static::setRoles();
            self::$arrInstances[ $strInstanceKey ] = new self;
        }

        return self::$arrInstances[ $strInstanceKey ];
    }

    protected function setRoles() {

        \Controller::loadDataContainer(self::$strTable);
        \System::loadLanguageFile(self::$strTable);

        $arrRoles = [];
        $arrFields = $GLOBALS['TL_DCA'][self::$strTable]['fields'] ?: [];

        if ( empty( $arrFields ) ) {
            return $arrRoles;
        }

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
                'role' => $GLOBALS['CM_ROLES'][ $arrField['eval']['role'] ],
                'value' => isset( self::$arrEntity[ $strFieldname ] ) ? self::$arrEntity[ $strFieldname ] : ''
            ];
        }

        if ( isset( $GLOBALS['TL_HOOKS']['roleResolverSetRoles'] ) && is_array($GLOBALS['TL_HOOKS']['roleResolverSetRoles'] ) ) {
            foreach ( $GLOBALS['TL_HOOKS']['roleResolverSetRoles'] as $arrCallback ) {
                $arrRoles = static::importStatic($arrCallback[0])->{$arrCallback[1]}($arrRoles, self::$arrEntity, self::$strTable);
            }
        }

        return $arrRoles;
    }

    public function getRole( $strRolename ) {

        return $GLOBALS['CM_ROLES'][ $strRolename ];
    }

    public function getValueByRole( $strRolename ) {

        if ( !isset( self::$arrRoles[ $strRolename ] ) ) {
            return '';
        }

        return self::$arrRoles[ $strRolename ]['value'];
    }

    public function getFieldByRole( $strRolename ) {

        if ( !isset( self::$arrRoles[ $strRolename ] ) ) {
            return '';
        }

        return self::$arrRoles[ $strRolename ]['name'];
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