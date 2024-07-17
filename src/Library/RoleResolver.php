<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Entity\Roles;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder;
use Contao\Controller;
use Contao\System;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class RoleResolver
{

    public static null|string $strTable = null;

    public static null|array $arrRoles = null;

    public static null|array $arrEntity = null;

    protected static null|array $arrInstances = [];

    protected static FilesystemAdapter $objCache;

    protected static Roles $objRoles;

    public static function getInstance($strTable, $arrEntity = [])
    {

        self::$objRoles = new Roles();

        if ($strTable === null) {
            return new self;
        }

        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');
        self::$objCache = new FilesystemAdapter('', 0, $strRootDir . '/var/cache');

        $strInstanceKey = 'roles_' . $strTable . ($arrEntity['id'] ? '_' . $arrEntity['id'] : '') . (md5($arrEntity['tstamp'] ?? '0'));

        if (!array_key_exists($strInstanceKey, self::$arrInstances)) {
            self::$strTable = $strTable;
            self::$arrEntity = $arrEntity;
            self::$arrInstances[$strInstanceKey] = new self;
        }

        self::$arrRoles = self::$objCache->get($strInstanceKey, function (ItemInterface $item) {
            $arrReturn = static::setRoles();
            $item->expiresAfter(60);
            $item->set($arrReturn);
            return $arrReturn;
        });

        return self::$arrInstances[$strInstanceKey];
    }

    protected static function setRoles(): array
    {

        Controller::loadDataContainer(self::$strTable);
        System::loadLanguageFile(self::$strTable);

        $arrRoles = [];
        $arrFields = $GLOBALS['TL_DCA'][self::$strTable]['fields'] ?: [];

        if (empty($arrFields)) {
            return $arrRoles;
        }

        foreach ($arrFields as $strFieldname => $arrField) {

            if (!isset($arrField['eval'])) {
                continue;
            }

            if (!isset($arrField['eval']['role'])) {
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
                'type' => ($arrField['inputType'] ?? ''),
                'role' => (self::$objRoles)->get()[$strRole] ?? '',
                'value' => self::$arrEntity[$strFieldname] ?? ''
            ];
        }

        if (isset($GLOBALS['TL_HOOKS']['roleResolverSetRoles']) && is_array($GLOBALS['TL_HOOKS']['roleResolverSetRoles'])) {
            foreach ($GLOBALS['TL_HOOKS']['roleResolverSetRoles'] as $arrCallback) {
                $arrRoles = System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrRoles, self::$arrEntity, self::$strTable);
            }
        }

        return $arrRoles;
    }

    public function getRole($strRoleName): array
    {
        return (self::$objRoles)->get()[$strRoleName] ?? [];
    }

    public function getValueByRole($strRoleName): mixed
    {

        if (!isset(self::$arrRoles[$strRoleName])) {
            return '';
        }

        return self::$arrRoles[$strRoleName]['value'];
    }

    public function getFieldByRole($strRoleName): string
    {

        if (!isset(self::$arrRoles[$strRoleName])) {
            return '';
        }

        return self::$arrRoles[$strRoleName]['name'];
    }

    public function getFieldsByRoles($arrRoles): null|array
    {

        $arrReturn = [];
        foreach ($arrRoles as $strRole) {
            $strValue = $this->getValueByRole($strRole);
            if ($strValue) {
                $arrReturn[$strRole] = $strValue;
            }
        }

        return empty($arrReturn) ? null : $arrReturn;
    }

    public function getGeoCodingAddress($strDelimiter = ', '): string
    {

        $arrAddress = [];
        $arrAddressRoles = ['street', 'streetNumber', 'zip', 'postal', 'city', 'state', 'country', 'address'];

        foreach ($arrAddressRoles as $strRole) {
            if ($strValue = $this->getValueByRole($strRole)) {
                $arrAddress[$strRole] = Toolkit::parse($strValue, ' ');
            }
        }

        return (new AddressBuilder($arrAddress))->getAddress($strDelimiter);
    }

    public function getGeoCodingFields(): array
    {

        $arrReturn = [];
        $arrGeoRoles = ['latitude', 'longitude'];

        foreach ($arrGeoRoles as $strRole) {
            $arrReturn[$strRole] = self::$arrRoles[$strRole]['name'];
        }

        return $arrReturn;
    }

    protected function getKeyValueByRoles($arrRoles): array
    {

        $arrReturn = [];
        foreach ($arrRoles as $strRole) {
            if (!isset(self::$arrRoles[$strRole]['name'])) {
                continue;
            }
            $arrReturn[self::$arrRoles[$strRole]['name']] = self::$arrEntity[self::$arrRoles[$strRole]['name']];
        }

        return $arrReturn;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }
}