<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\System;

class LoadDataContainerListener
{

    public function __invoke(string $strTable): void
    {

        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (!$objRequest) {
            return;
        }

        if ($objRequest->get('_route') == 'contao_install') {
            return;
        }

        if ($strTable && !isset($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])) {

            $objCatalog = CatalogModel::findByTableOrModule($strTable);

            if (!$objCatalog) {
                return;
            }

            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeDataContainerArrayByTable($strTable);
        }
    }
}

