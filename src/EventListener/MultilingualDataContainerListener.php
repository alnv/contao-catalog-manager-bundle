<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Helper\MultilingualHelper;
use Terminal42\DcMultilingualBundle\Driver;

class MultilingualDataContainerListener
{
    public function onLoadVirtualDataContainer($strTable): void
    {
        $strDataContainer = $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] ?? null;

        if ($strDataContainer !== Driver::class && $strDataContainer !== 'Multilingual') {
            return;
        }

        $arrLanguages = MultilingualHelper::getLanguages();

        $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] = Driver::class;
        $GLOBALS['TL_DCA'][$strTable]['config']['_table'] = $strTable;
        $GLOBALS['TL_DCA'][$strTable]['config']['langPid'] = 'lid';
        $GLOBALS['TL_DCA'][$strTable]['config']['langColumnName'] = 'language';
        $GLOBALS['TL_DCA'][$strTable]['config']['fallbackLang'] = $arrLanguages['fallback'] ?? '';
        $GLOBALS['TL_DCA'][$strTable]['config']['languages'] = $arrLanguages['languages'] ?? [];
        $GLOBALS['TL_DCA'][$strTable]['fields']['alias']['eval']['isMultilingualAlias'] = true;
    }

    public function onLoadDataContainer($strTable): void
    {
        if (($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] ?? null) === 'Multilingual') {
            $GLOBALS['TL_DCA'][$strTable]['config']['_table'] = $strTable;
        }
    }
}
