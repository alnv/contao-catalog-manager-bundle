<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;


class DataContainer {


    public function generateDataContainerArray( $strTable ) {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( $objRequest === null ) {

            return null;
        }

        if ( $objRequest->get( '_scope' ) == 'frontend' ) {

            $objCatalog = CatalogModel::findByTableOrModule( $strTable );

            if ( $objCatalog !== null ) {

                $objVirtualDataContainerArray = new Application();
                $objVirtualDataContainerArray->initializeDataContainerArrayByTable( $strTable );
            }
        }
    }
}