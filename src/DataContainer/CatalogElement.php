<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Contao\Database;

class CatalogElement
{

    public function getArticleElements(): array
    {

        $arrReturn = [];
        $objElements = Database::getInstance()->prepare('SELECT * FROM tl_catalog_element WHERE `type`=?')->execute('article');
        if (!$objElements->numRows) {
            return $arrReturn;
        }

        while ($objElements->next()) {
            $arrReturn[$objElements->id] = $objElements->title;
        }

        return $arrReturn;
    }
}