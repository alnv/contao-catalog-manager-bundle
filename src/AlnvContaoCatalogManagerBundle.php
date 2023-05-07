<?php

namespace Alnv\ContaoCatalogManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AlnvContaoCatalogManagerBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}