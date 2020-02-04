<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class MapModule extends Listing {

    protected $strTemplate = 'mod_listing_map';

    protected function compile() {

        $this->arrOptions = [];
        $this->setFilter();
        $this->setMasterPage();
        $this->setPagination();
        $this->setDistance();

        $this->Template->map = (new \Alnv\ContaoCatalogManagerBundle\Maps\GMap($this->cmTable,$this->arrOptions))->render();
    }
}