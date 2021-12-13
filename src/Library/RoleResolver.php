<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

class RoleResolver extends \System {

    public static $strTable = null;
    public static $arrRoles = null;
    public static $arrEntity = null;
    protected static $arrInstances = [];

    public static function getInstance($strTable, $arrEntity=[]) {

        if ($strTable === null) {
            return new self;
        }

        $strInstanceKey = 'roles_' . $strTable . ($arrEntity['id'] ? '_' . $arrEntity['id'] : '');

        if (!array_key_exists($strInstanceKey, self::$arrInstances)) {
            self::$strTable = $strTable;
            self::$arrEntity = $arrEntity;
            self::$arrInstances[$strInstanceKey] = new self;
        }

        if (!\Cache::has($strInstanceKey)) {
            \Cache::set($strInstanceKey, static::setRoles());
        }
        self::$arrRoles = \Cache::get($strInstanceKey);

        return self::$arrInstances[$strInstanceKey];
    }

    protected static function setRoles() {

        \Controller::loadDataContainer(self::$strTable);
        \System::loadLanguageFile(self::$strTable);

        $arrRoles = [];
        $arrFields = $GLOBALS['TL_DCA'][self::$strTable]['fields'] ?: [];

        if (empty($arrFields)) {
            return $arrRoles;
        }

        foreach ($arrFields as $strFieldname => $arrField) {

            if (!isset($arrField['eval'])) {
                continue;
            }

            if (!$arrField['eval']['role']) {
                continue;
            }

            $strRole = $arrField['eval']['role'] ?: '';
            if (!$strRole) {
                continue;
            }

            if (isset($arrRoles[$strRole])) {
                continue;
            }

            $arrRoles[$strRole] = [
                'name' => $strFieldname,
                'eval' => $arrField['eval'],
                'label' => $arrField['label'],
                'type' => $arrField['inputType'],
                'role' => $GLOBALS['CM_ROLES'][$strRole],
                'value' => isset(self::$arrEntity[$strFieldname]) ? self::$arrEntity[$strFieldname] : ''
            ];
        }

        if (isset($GLOBALS['TL_HOOKS']['roleResolverSetRoles']) && is_array($GLOBALS['TL_HOOKS']['roleResolverSetRoles'])) {
            foreach ($GLOBALS['TL_HOOKS']['roleResolverSetRoles'] as $arrCallback) {
                $arrRoles = static::importStatic($arrCallback[0])->{$arrCallback[1]}($arrRoles, self::$arrEntity, self::$strTable);
            }
        }

        return $arrRoles;
    }

    public function getRole($strRolename) {

        return $GLOBALS['CM_ROLES'][$strRolename];
    }

    public function getValueByRole($strRolename) {

        if (!isset(self::$arrRoles[$strRolename])) {
            return '';
        }

        return self::$arrRoles[$strRolename]['value'];
    }

    public function getFieldByRole($strRolename) {

        if (!isset(self::$arrRoles[$strRolename])) {
            return '';
        }

        return self::$arrRoles[$strRolename]['name'];
    }

    public function getFieldsByRoles($arrRoles) {

        $arrReturn = [];
        foreach ($arrRoles as $strRole) {
            $strValue = $this->getValueByRole($strRole);
            if ($strValue) {
                $arrReturn[$strRole] = $strValue;
            }
        }

        return empty($arrReturn) ? null : $arrReturn;
    }

    public function getGeoCodingAddress($strDelimiter=', ') {

        $arrAddress = [];
        $arrAddressRoles = ['street', 'streetNumber', 'zip', 'city', 'state', 'country'];

        foreach ($arrAddressRoles as $strRole) {
            if ($strValue = $this->getValueByRole($strRole)) {
                $arrAddress[$strRole] = $strValue;
            }
        }

        $objAddress = new \Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder($arrAddress);
        return $objAddress->getAddress($strDelimiter);
    }

    public function getGeoCodingFields() {

        $arrReturn = [];
        $arrGeoRoles = ['latitude', 'longitude'];

        foreach ($arrGeoRoles as $strRole) {
            $arrReturn[$strRole] = self::$arrRoles[$strRole]['name'];
        }

        return $arrReturn;
    }

    protected function getKeyValueByRoles($arrRoles) {

        $arrReturn = [];

        foreach ($arrRoles as $strRole) {

            if (!isset(self::$arrRoles[$strRole]['name'])) {
                continue;
            }

            $arrReturn[self::$arrRoles[ $strRole ]['name']] = self::$arrEntity[self::$arrRoles[$strRole]['name']];
        }

        return $arrReturn;
    }

    private function __clone(){}
    public function __wakeup(){}
}