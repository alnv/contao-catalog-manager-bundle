<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Library\Application;

class Initialize {

    public function initializeBackendModules() {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( !$objRequest ) {

            return null;
        }

        if ( $objRequest->get('_route') == 'contao_install' ) {

            return null;
        }

        if ( $objRequest->get('_scope') == 'backend' ) {

            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeBackendModules();
        }
    }

    public function generateDataContainerArray() {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( !$objRequest ) {

            return null;
        }

        if ( $objRequest->get('_route') == 'contao_install' ) {

            return null;
        }

        if ( $objRequest->get('_scope') == 'backend' ) {

            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeDataContainerArrays();
        }
    }
}