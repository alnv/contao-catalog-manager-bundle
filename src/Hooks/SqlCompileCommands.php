<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Contao\Database;

class SqlCompileCommands
{

    public function execute($arrSQLCommands)
    {

        if (!Database::getInstance()->tableExists('tl_catalog')) {
            return $arrSQLCommands;
        }

        foreach ($arrSQLCommands as $strType => $arrSQLCommandGroup) {
            switch ($strType) {
                case 'DROP':
                    foreach ($arrSQLCommandGroup as $strHex => $strSqlCommand) {
                        $strTable = str_replace('DROP TABLE', '', $strSqlCommand);
                        $strTable = str_replace(' ', '', $strTable);
                        $objCatalog = Database::getInstance()->prepare('SELECT * FROM tl_catalog WHERE `table`=?')->limit(1)->execute($strTable);
                        if ($objCatalog->numRows) {
                            unset($arrSQLCommands[$strType][$strHex]);
                        }
                    }
                    if (empty($arrSQLCommands[$strType])) {
                        unset($arrSQLCommands[$strType]);
                    }
                    break;
            }
        }

        return $arrSQLCommands;
    }
}