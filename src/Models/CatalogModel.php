<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Contao\Database;
use Contao\Model;

class CatalogModel extends Model
{

    protected static $strTable = 'tl_catalog';

    public static function findByTableOrModule($strIdentifier, array $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.table=? OR $strT.module=? OR $strT.id=?"];

        return static::findOneBy($arrColumns, [$strIdentifier, $strIdentifier, (int)$strIdentifier], $arrOptions);
    }

    public static function findChildrenCatalogsById($strId)
    {

        $strT = static::$strTable;
        $objChildTables = Database::getInstance()
            ->prepare('SELECT * FROM ' . $strT . ' WHERE pid=? ORDER BY sorting DESC')
            ->execute($strId);

        if ($objChildTables->numRows < 1) {
            return null;
        }

        return static::createCollectionFromDbResult($objChildTables, 'tl_catalog');
    }

    public static function findParentCatalogByTable($strTable)
    {

        $strT = static::$strTable;
        $objParent = Database::getInstance()->prepare('SELECT * FROM ' . $strT . ' WHERE id=(SELECT pid FROM ' . $strT . ' WHERE `table`=? LIMIT 1)')->limit(1)->execute($strTable);

        if ($objParent->numRows < 1) {
            return null;
        }

        return static::createCollectionFromDbResult($objParent, 'tl_catalog');
    }
}