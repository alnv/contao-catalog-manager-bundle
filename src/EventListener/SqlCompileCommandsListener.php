<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\Database;

class SqlCompileCommandsListener
{
    public function __invoke(array $arrSQLCommands): array
    {
        return $arrSQLCommands;
        /*
        if (!Database::getInstance()->tableExists('tl_catalog')) {
            return $arrSQLCommands;
        }

        foreach ($arrSQLCommands as $strType => $arrSQLCommandGroup) {

            if ($strType == 'DROP') {
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
            }
        }

        return $arrSQLCommands;
        */
    }
}