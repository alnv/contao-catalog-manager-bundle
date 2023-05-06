<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\FrontendUser;
use Contao\Model;

class CatalogReactionsDataModel extends Model
{

    protected static $strTable = 'tl_catalog_reactions_data';

    public static function getReaction($strTable, $strIdentifier, $arrOptions = [])
    {

        $strT = static::$strTable;
        $arrColumns = ["$strT.table=?", "$strT.identifier=?"];
        $arrValues = [$strTable, $strIdentifier];
        $arrOptions['limit'] = 1;

        if (FrontendUser::getInstance()->id) {
            $arrColumns[] = "($strT.session=? OR $strT.member=?)";
            $arrValues[] = Toolkit::getSessionId();
            $arrValues[] = FrontendUser::getInstance()->id;
        } else {
            $arrColumns[] = "$strT.session=?";
            $arrValues[] = Toolkit::getSessionId();
        }

        return static::findOneBy($arrColumns, $arrValues, $arrOptions);
    }
}