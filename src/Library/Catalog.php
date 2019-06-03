<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\CatalogManagerBundle\Models\CatalogModel;
use Alnv\CatalogManagerBundle\Helper\CatalogWizard;
use Alnv\CatalogManagerBundle\Models\CatalogFieldModel;


class Catalog extends CatalogWizard {


    protected $arrFields = [];
    protected $arrCatalog = [];
    protected $strIdentifier = null;


    public function __construct( $strIdentifier ) {

        $this->strIdentifier = $strIdentifier;
        $this->setCatalog();
    }


    public function getCatalog() {

        return $this->arrCatalog;
    }


    public function getFields() {

        return $this->arrFields;
    }


    public function getNaturalFields( $blnLabelOnly = true ) {

        $arrReturn = [];

        foreach ( $this->arrFields as $strFieldname => $arrField ) {

            $arrReturn[ $strFieldname ] = $strFieldname;
        }

        return $arrReturn;
    }


    protected function setCatalog() {

        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ( $objCatalog === null ) {

            return null;
        }

        $this->setFields( $objCatalog->id );

        $this->arrCatalog = $this->parseCatalog( $objCatalog->row() );
    }


    protected function setFields( $intPid ) {

        $this->getDefaultFields();

        $objFields = CatalogFieldModel::findByPid( $intPid );

        if ( $objFields === null ) {

            return null;
        }

        while ( $objFields->next() ) {

            $this->arrFields[ $objFields->fieldname ] = $this->parseField( $objFields->row() );
        }
    }


    protected function getDefaultFields() {

        array_insert( $this->arrFields, 0, [

            'id' => [],
            'pid' => [],
            'sorting' => [],
            'tstamp' => [],
            'invisible' => [],
            'start' => [],
            'stop' => [],
            'alias' => []
        ]);
    }
}