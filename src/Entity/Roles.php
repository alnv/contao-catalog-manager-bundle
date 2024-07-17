<?php

namespace Alnv\ContaoCatalogManagerBundle\Entity;

use Contao\Database;

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
        $arrRoles = ($GLOBALS['CM_ROLES'] ?? []);

        foreach ($this->getCustomRoles() as $strName => $arrRole) {
            $arrRoles[$strName] = $arrRole;
        }

        $this->arrRoles = $arrRoles;
    }

    private function getCustomRoles(): array
    {

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

            $arrRoles[$objRoles->name] = $arrRole;
        }

        return $arrRoles;
    }
}