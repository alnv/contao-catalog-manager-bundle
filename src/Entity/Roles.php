<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Contao\Database;
use Alnv\ContaoCatalogManagerBundle\Helper\Cache;

class Roles
{
    protected static ?array $runtimeCache = null;

    protected array $arrRoles = [];

    public function __construct()
    {
        if (self::$runtimeCache !== null) {
            $this->arrRoles = self::$runtimeCache;
            return;
        }

        $this->setup();

        self::$runtimeCache = $this->arrRoles;
    }

    public function get(): array
    {
        return $this->arrRoles;
    }

    protected function setup(): void
    {
        if (Cache::has('all_roles')) {
            $this->arrRoles = Cache::get('all_roles');
            return;
        }

        $arrRoles = ($GLOBALS['CM_ROLES'] ?? []);
        foreach ($this->getCustomRoles() as $strName => $arrRole) {
            $arrRoles[$strName] = $arrRole;
        }

        $this->arrRoles = $arrRoles;
        Cache::set('all_roles', $this->arrRoles);
    }

    private function getCustomRoles(): array
    {
        if (Cache::has('custom_roles')) {
            return Cache::get('custom_roles');
        }

        $arrRoles = [];
        $objRoles = Database::getInstance()
            ->prepare('SELECT * FROM tl_catalog_roles ORDER BY name ASC')
            ->execute();

        if ($objRoles === null) {
            return [];
        }

        while ($objRoles->next()) {
            if (!$objRoles->name) {
                continue;
            }

            $arrRole = [
                'group' => 'miscellaneous',
                'eval' => [],
                'sql' => $objRoles->sql ?: ''
            ];

            if ($objRoles->maxlength !== null && $objRoles->maxlength !== '') {
                $arrRole['eval']['maxlength'] = (int)$objRoles->maxlength;
            }
            if ($objRoles->minlength !== null && $objRoles->minlength !== '') {
                $arrRole['eval']['minlength'] = (int)$objRoles->minlength;
            }
            if ($objRoles->minval !== null && $objRoles->minval !== '') {
                $arrRole['eval']['minval'] = (int)$objRoles->minval;
            }
            if ($objRoles->maxval !== null && $objRoles->maxval !== '') {
                $arrRole['eval']['maxval'] = (int)$objRoles->maxval;
            }

            if ($objRoles->class) {
                $arrRole['eval']['tl_class'] = $objRoles->class;
            }

            if ($objRoles->rgxp) {
                $arrRole['eval']['rgxp'] = $objRoles->rgxp;
            }

            if ($objRoles->isUnique) {
                $arrRole['eval']['unique'] = true;
            }

            $arrRoles[$objRoles->name] = $arrRole;
        }

        Cache::set('custom_roles', $arrRoles);

        return $arrRoles;
    }
}