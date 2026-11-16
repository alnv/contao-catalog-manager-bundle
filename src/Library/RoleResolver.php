<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Entity\Roles;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder;
use Contao\Controller;
use Contao\System;

/**
 * Ordnet Felder eines Katalogs ihren Rollen (title, teaser, startDate …) zu.
 *
 * Performance: Früher wurde pro Eintrag ein Item in einem FilesystemAdapter (var/cache/cm.roles.cache,
 * TTL 60 s) geschrieben – ein Dateisystem-Schreibzugriff pro Eintrag und Request, ein wachsendes
 * Cache-Verzeichnis und bis zu 60 s veraltete Werte. Jetzt:
 * - Rollen-Definitionen (aus dem DCA) werden einmal pro Tabelle und Request ermittelt,
 * - die Werte pro Eintrag werden im Speicher des Requests gehalten,
 * - jede Instanz hält ihre eigenen Rollen (vorher statisch geteilt → die zuletzt geladene
 *   Instanz bestimmte die Werte aller anderen).
 */
class RoleResolver
{
    /** @deprecated Nur noch aus Kompatibilitätsgründen befüllt (zuletzt geladene Instanz). */
    public static null|string $strTable = null;

    /** @deprecated Nur noch aus Kompatibilitätsgründen befüllt (zuletzt geladene Instanz). */
    public static null|array $arrRoles = null;

    /** @deprecated Nur noch aus Kompatibilitätsgründen befüllt (zuletzt geladene Instanz). */
    public static null|array $arrEntity = null;

    /** Obergrenze für gemerkte Instanzen pro Request (Speicher bei sehr großen Listen begrenzen) */
    private const MAX_INSTANCES = 2000;

    protected static null|array $arrInstances = [];

    /** @var array<string, array> Rollen-Definitionen pro Tabelle (ohne Werte) */
    protected static array $arrDefinitions = [];

    protected static ?Roles $objRoles = null;

    protected array $roles = [];

    protected array $entity = [];

    protected ?string $table = null;

    public static function getInstance($strTable, $arrEntity = [])
    {
        self::$objRoles ??= new Roles();

        if ($strTable === null) {
            return new self;
        }

        $arrEntity = \is_array($arrEntity) ? $arrEntity : [];
        $strInstanceKey = self::buildKey((string)$strTable, $arrEntity);

        if (!isset(self::$arrInstances[$strInstanceKey])) {
            if (\count(self::$arrInstances) >= self::MAX_INSTANCES) {
                self::$arrInstances = [];
            }

            $objInstance = new self;
            $objInstance->table = (string)$strTable;
            $objInstance->entity = $arrEntity;
            $objInstance->roles = self::resolveRoles((string)$strTable, $arrEntity);

            self::$arrInstances[$strInstanceKey] = $objInstance;
        }

        $objInstance = self::$arrInstances[$strInstanceKey];

        // Kompatibilität: statische Properties zeigen auf die zuletzt angeforderte Instanz
        self::$strTable = $objInstance->table;
        self::$arrEntity = $objInstance->entity;
        self::$arrRoles = $objInstance->roles;

        return $objInstance;
    }

    protected static function buildKey(string $strTable, array $arrEntity): string
    {
        if (!empty($arrEntity['id'])) {
            return $strTable . ':' . $arrEntity['id'] . ':' . ($arrEntity['tstamp'] ?? '');
        }

        // Ohne ID (z. B. Formulare): über die skalaren Werte unterscheiden – vorher teilten sich
        // alle Einträge ohne ID denselben Cache-Eintrag
        $arrScalar = \array_filter($arrEntity, static fn ($v) => \is_scalar($v) || $v === null);

        return $strTable . ':h:' . \md5(\serialize($arrScalar));
    }

    protected static function getDefinitions(string $strTable): array
    {
        if (isset(self::$arrDefinitions[$strTable])) {
            return self::$arrDefinitions[$strTable];
        }

        Controller::loadDataContainer($strTable);
        System::loadLanguageFile($strTable);

        $arrDefinitions = [];
        $arrAvailableRoles = self::$objRoles->get();

        foreach (($GLOBALS['TL_DCA'][$strTable]['fields'] ?? []) ?: [] as $strFieldname => $arrField) {

            $strRole = $arrField['eval']['role'] ?? '';

            if (!$strRole || isset($arrDefinitions[$strRole])) {
                continue;
            }

            $arrDefinitions[$strRole] = [
                'name' => $strFieldname,
                'eval' => $arrField['eval'],
                'label' => $arrField['label'] ?? '',
                'type' => ($arrField['inputType'] ?? ''),
                'role' => $arrAvailableRoles[$strRole] ?? '',
            ];
        }

        return self::$arrDefinitions[$strTable] = $arrDefinitions;
    }

    protected static function resolveRoles(string $strTable, array $arrEntity): array
    {
        $arrRoles = [];

        foreach (self::getDefinitions($strTable) as $strRole => $arrDefinition) {
            $arrDefinition['value'] = $arrEntity[$arrDefinition['name']] ?? '';
            $arrRoles[$strRole] = $arrDefinition;
        }

        if (isset($GLOBALS['TL_HOOKS']['roleResolverSetRoles']) && is_array($GLOBALS['TL_HOOKS']['roleResolverSetRoles'])) {
            foreach ($GLOBALS['TL_HOOKS']['roleResolverSetRoles'] as $arrCallback) {
                $arrRoles = System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrRoles, $arrEntity, $strTable);
            }
        }

        return $arrRoles;
    }

    public function getRole($strRoleName): array
    {
        self::$objRoles ??= new Roles();

        return self::$objRoles->get()[$strRoleName] ?? [];
    }

    public function getValueByRole($strRoleName): mixed
    {
        return $this->roles[$strRoleName]['value'] ?? '';
    }

    public function getFieldByRole($strRoleName): string
    {
        return (string)($this->roles[$strRoleName]['name'] ?? '');
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
            $arrReturn[$strRole] = $this->roles[$strRole]['name'] ?? '';
        }

        return $arrReturn;
    }

    protected function getKeyValueByRoles($arrRoles): array
    {

        $arrReturn = [];
        foreach ($arrRoles as $strRole) {
            if (!isset($this->roles[$strRole]['name'])) {
                continue;
            }
            $strField = $this->roles[$strRole]['name'];
            $arrReturn[$strField] = $this->entity[$strField] ?? null;
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