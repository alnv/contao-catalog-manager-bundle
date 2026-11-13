<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Contao\DC_Table;

class PlaceholderDataContainer extends DC_Table
{

    public function __construct($strTable, $arrModule=array())
    {
    }

    protected function save($varValue)
    {
        //
    }

    public function getPalette()
    {
        return '';
    }
}