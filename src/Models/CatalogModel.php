<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Contao\Database;
use Contao\Model;

class CatalogModel extends Model
{

    protected static $strTable = 'tl_catalog';

    public static function findByTableOrModule($strIdentifier, array $arrOptions = [])
    {

        $table = static::$strTable;
        $arrColumns = ["$table.`table`=? OR $table.`module`=? OR $table.`id`=?"];

        return static::findOneBy($arrColumns, [$strIdentifier, $strIdentifier, (int)$strIdentifier], $arrOptions);
    }

    public static function findChildrenCatalogsById($strId)
    {

        $table = static::$strTable;
        $objChildTables = Database::getInstance()
            ->prepare('SELECT * FROM ' . $table . ' WHERE pid=? ORDER BY `sorting` DESC')
            ->execute($strId);

        if ($objChildTables->numRows < 1) {
            return null;
        }

        return static::createCollectionFromDbResult($objChildTables, 'tl_catalog');
    }

    public static function findParentCatalogByTable($strTable)
    {

        $table = static::$strTable;
        $objParent = Database::getInstance()->prepare('SELECT * FROM ' . $table . ' WHERE id=(SELECT pid FROM ' . $table . ' WHERE `table`=? LIMIT 1)')->limit(1)->execute($strTable);

        if ($objParent->numRows < 1) {
            return null;
        }

        return static::createCollectionFromDbResult($objParent, 'tl_catalog');
    }
}