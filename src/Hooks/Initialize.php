<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Library\Application;


class Initialize {


    protected  $strMode = null;


    public function __construct() {

        $objRequest = \System::getContainer()->get( 'request_stack' )->getCurrentRequest();

        if ( $objRequest !== null ) {

            $this->strMode = $objRequest->get( '_route' );
        }
    }


    public function initializeBackendModules() {

        if ( !$this->strMode ) {

            return null;
        }

        if ( $this->strMode == 'contao_backend' ) {

            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeBackendModules();
        }
    }


    public function generateDataContainerArray() {

        if ( !$this->strMode ) {

            return null;
        }

        if ( $this->strMode == 'contao_backend' ) {

            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeDataContainerArrays();
        }
    }
}