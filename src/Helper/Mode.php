<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

class Mode
{

    public static function get(): string
    {

        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            return 'BE';
        }

        if (System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            return 'FE';
        }

        return '';
    }
}