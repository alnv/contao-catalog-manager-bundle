<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

class CatalogReactionsDataModel extends \Model {

    protected static $strTable = 'tl_catalog_reactions_data';

    public static function getReaction($strTable, $strIdentifier, $arrOptions=[]) {

        $strT= static::$strTable;
        $arrColumns = [ "$strT.table=?",  "$strT.identifier=?"]; // "$strT.table=? AND $strT.identifier=? AND ($strT.session=? OR $strT.member=?)"
        $arrValues = [$strTable, $strIdentifier]; // \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId(), (\FrontendUser::getInstance()->id?:0)
        $arrOptions['limit'] = 1;

        if (\FrontendUser::getInstance()->id) {
            $arrColumns[] = "($strT.session=? OR $strT.member=?)";
            $arrValues[] = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId();
            $arrValues[] = \FrontendUser::getInstance()->id;
        } else {
            $arrColumns[] = "$strT.session=?";
            $arrValues[] = \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getSessionId();
        }

        return static::findOneBy($arrColumns, $arrValues, $arrOptions);
    }
}