<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;


class VirtualDataContainerArray {


    protected $arrCatalog = [];
    protected $arrFields = [];


    public function __construct( $strModule ) {

        $objCatalog = new Catalog( $strModule );
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();
        $this->generateEmptyDataContainer();
    }


    protected function setConfig() {

        //
    }


    protected function setList() {

        //
    }



    protected function setFields() {

        //
    }


    protected function setPalettes() {


    }


    protected function setSubPalettes() {

        //
    }


    protected function generateEmptyDataContainer() {

        $GLOBALS['TL_DCA'][ $this->arrCatalog['table'] ] = [
            'config' => [ 'dataContainer' => 'Table' ],
            'list' => [
                'label' => [],
                'sorting' => [],
                'operations' => [],
                'global_operations' => []
            ],
            'palettes' => [ '__selector__' => [], 'default' => '' ],
            'subpalettes' => [],
            'fields' => []
        ];
    }


    public function getRelatedTables() {

        return $this->arrCatalog['children'];
    }


    public function generate() {

        $this->setConfig();
        $this->setList();
        $this->setPalettes();
        $this->setSubPalettes();
        $this->setFields();
    }
}