<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Contao\System;

class InitializeSystemListener
{

    public function __invoke(): void
    {
        $this->initializeBackendModules();
        $this->generateDataContainerArray();
    }

    public function initializeBackendModules(): void
    {

        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();
        if (!$objRequest) {
            return;
        }

        if ($objRequest->get('_route') == 'contao_install') {
            return;
        }

        if ($objRequest->get('_scope') == 'backend') {
            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeBackendModules();
        }
    }

    public function generateDataContainerArray(): void
    {

        $objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

        if (!$objRequest) {
            return;
        }

        if ($objRequest->get('_route') == 'contao_install') {
            return;
        }

        if ($objRequest->get('_scope') == 'backend') {
            $objVirtualDataContainerArray = new Application();
            $objVirtualDataContainerArray->initializeDataContainerArrays();
        }
    }
}