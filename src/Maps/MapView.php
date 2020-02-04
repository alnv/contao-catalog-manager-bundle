<?php

namespace Alnv\ContaoCatalogManagerBundle\Maps;

abstract class MapView {

    protected $strTable = null;
    protected $arrOptions = [];

    public function __construct( $strTable, $arrOptions = [] ) {

        $this->strTable = $strTable;
        $this->arrOptions = $arrOptions;
    }

    protected function getLocations() {

        return array_map(function ($arrLocation){
            $arrLocation['map'] = [];
            $arrLocation['map']['street'] = $arrLocation['roleResolver']()->getValueByRole('street');
            $arrLocation['map']['streetNumber'] = $arrLocation['roleResolver']()->getValueByRole('streetNumber');
            $arrLocation['map']['city'] = $arrLocation['roleResolver']()->getValueByRole('city');
            $arrLocation['map']['zip'] = $arrLocation['roleResolver']()->getValueByRole('zip');
            $arrLocation['map']['country'] = $arrLocation['roleResolver']()->getValueByRole('country');
            $arrLocation['map']['location'] = $arrLocation['roleResolver']()->getValueByRole('location');
            $arrLocation['map']['title'] = $arrLocation['roleResolver']()->getValueByRole('title');
            $arrLocation['map']['text'] = $arrLocation['roleResolver']()->getValueByRole('teaser');
            $arrLocation['map']['latitude'] = $arrLocation['roleResolver']()->getValueByRole('latitude');
            $arrLocation['map']['longitude'] = $arrLocation['roleResolver']()->getValueByRole('longitude');
            return $arrLocation;
        }, (new \Alnv\ContaoCatalogManagerBundle\Views\Listing( $this->strTable, $this->arrOptions ))->parse());
    }

    abstract public function render();
}