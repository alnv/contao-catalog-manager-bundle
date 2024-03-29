<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class MapModule extends \Alnv\ContaoCatalogManagerBundle\Modules\Listing {

    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_module';
    protected $strTemplate = 'mod_listing_map';

    protected function compile() {

        $this->arrOptions = [
            'infoContent' => $this->cmInfoContent ?: '',
            'template' => $this->cmTemplate ?: 'cm_map_view_gmap'
        ];
        $this->setOrder();
        $this->setFilter();
        $this->setMasterPage();
        if (!$this->setDistance()) {
            $this->setPagination();
        }
        $this->Template->map = (new \Alnv\ContaoCatalogManagerBundle\Maps\GMap($this->cmTable, $this->arrOptions))->render();
    }
}