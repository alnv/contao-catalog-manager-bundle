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

        $table = static::$strTable;
        $arrColumns = ["$table.`table`=?", "$table.`identifier`=?"];
        $arrValues = [$strTable, $strIdentifier];
        $arrOptions['limit'] = 1;

        if (FrontendUser::getInstance()->id) {
            $arrColumns[] = "($table.`session`=? OR $table.`member`=?)";
            $arrValues[] = Toolkit::getSessionId();
            $arrValues[] = FrontendUser::getInstance()->id;
        } else {
            $arrColumns[] = "$table.`session`=?";
            $arrValues[] = Toolkit::getSessionId();
        }

        return static::findOneBy($arrColumns, $arrValues, $arrOptions);
    }
}