<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogReactionsDataModel extends \Model {

    protected static $strTable = 'tl_catalog_reactions_data';

    public static function getReaction($strTable, $strIdentifier, $arrOptions=[]) {

        $strT= static::$strTable;
        $arrColumns = ["$strT.table=? AND $strT.identifier=? AND ($strT.session=? OR $strT.member=?)"];
        $arrOptions['limit'] = 1;

        return static::findOneBy($arrColumns, [$strTable, $strIdentifier, \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId(), (\FrontendUser::getInstance()->id?:0)], $arrOptions);
    }
}