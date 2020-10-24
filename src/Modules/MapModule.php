<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class MapModule extends \Alnv\ContaoCatalogManagerBundle\Modules\Listing {

    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_module';
    protected $strTemplate = 'mod_listing_map';

    protected function compile() {

        $this->arrOptions = [
            'infoContent' => $this->cmInfoContent ?: ''
        ];
        $this->setFilter();
        $this->setMasterPage();
        if (!$this->setDistance()) {
            $this->setPagination();
        }
        $this->Template->map = (new \Alnv\ContaoCatalogManagerBundle\Maps\GMap($this->cmTable, $this->arrOptions))->render();
    }
}