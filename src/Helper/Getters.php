<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Entity\PageFilter;
use Contao\Database;

class Getters
{

    public static function getPageFilterById($strPageFilterId): array
    {
        return Database::getInstance()->prepare('SELECT * FROM tl_page_filter WHERE id=?')->limit(1)->execute($strPageFilterId)->row();
    }

    public static function getPageFiltersByPageId($strPageId): array
    {

        $arrPageFilters = [];
        $objPageFilters = Database::getInstance()->prepare('SELECT * FROM tl_page_filter WHERE pid=? ORDER BY sorting ASC')->execute($strPageId);

        while ($objPageFilters->next()) {
            $arrPageFilters[] = new PageFilter($objPageFilters->id);
        }

        return $arrPageFilters;
    }
}