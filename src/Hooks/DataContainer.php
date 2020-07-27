<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;

class DataContainer {

    public function generateDataContainerArray( $strTable ) {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();
        if (!$objRequest) {
            return null;
        }
        if ($objRequest->get('_route') == 'contao_install') {
            return null;
        }

        if ($strTable && !isset($GLOBALS['TL_DCA'][$strTable])) {
            $objCatalog = CatalogModel::findByTableOrModule( $strTable );
            if ( $objCatalog !== null ) {
                $objVirtualDataContainerArray = new Application();
                $objVirtualDataContainerArray->initializeDataContainerArrayByTable( $strTable );
            }
        }
    }
}