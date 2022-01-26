<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogDataModel extends \Model {

    protected static $strTable = 'tl_catalog_data';

    public static function getByTypeAndTableAndIdentifier($strType, $strTable, $strIdentifier, $arrOptions=[]) {

        $strT= static::$strTable;
        $arrColumns = ["$strT.type=? AND $strT.table=? AND $strT.identifier=? AND ($strT.session=? OR $strT.member=?)"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strType, $strTable, $strIdentifier, \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId(), (\FrontendUser::getInstance()->id?:0)], $arrOptions);
    }

    public static function getLastAddedByType($strType, $arrOptions=[]) {

        $strT = static::$strTable;
        $arrOptions = [
            'value' => [$strType, \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId(), (\FrontendUser::getInstance()->id?:0)],
            'column' => ["$strT.type=? AND ($strT.session=? OR $strT.member=?)"],
            'order' => "$strT.tstamp DESC",
            'limit' => $arrOptions['limit']
        ];

        return static::findAll($arrOptions);
    }
}