<?php

namespace Alnv\CatalogManagerBundle\Hooks;

use Alnv\CatalogManagerBundle\Library\VirtualDataContainerArray;


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

            $objVirtualDataContainerArray = new VirtualDataContainerArray();
            $objVirtualDataContainerArray->initializeBackendModules();
        }
    }


    public function generateDataContainerArray() {

        if ( !$this->strMode ) {

            return null;
        }

        if ( $this->strMode == 'contao_backend' ) {

            $objVirtualDataContainerArray = new VirtualDataContainerArray();
            $objVirtualDataContainerArray->initializeDataContainerArrays();
        }
    }
}