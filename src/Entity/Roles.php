<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Contao\Database;
use Alnv\ContaoCatalogManagerBundle\Helper\Cache;

class Roles
{

    protected array $arrRoles = [];

    public function __construct()
    {
        $this->setup();
    }

    public function get()
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
        $objRoles = Database::getInstance()->prepare('SELECT * FROM tl_catalog_roles ORDER BY name ASC')->execute();

        while ($objRoles->next()) {

            if (!$objRoles->name) {
                continue;
            }

            $arrRole = [
                'group' => 'miscellaneous',
                'eval' => [],
                'sql' => $objRoles->sql ?: ''
            ];

            if ($objRoles->maxlength) {
                $arrRole['eval']['maxlength'] = (int)$objRoles->maxlength;
            }
            if ($objRoles->minlength) {
                $arrRole['eval']['minlength'] = (int)$objRoles->minlength;
            }
            if ($objRoles->minval) {
                $arrRole['eval']['minval'] = (int)$objRoles->minval;
            }
            if ($objRoles->maxval) {
                $arrRole['eval']['maxval'] = (int)$objRoles->maxval;
            }

            if ($objRoles->class) {
                $arrRole['eval']['tl_class'] = $objRoles->class;
            }

            if ($objRoles->rgxp) {
                $arrRole['eval']['rgxp'] = $objRoles->maxval;
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