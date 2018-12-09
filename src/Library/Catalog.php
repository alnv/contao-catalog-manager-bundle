<?php

namespace Alnv\CatalogManagerBundle\Library;

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


    protected function setCatalog() {

        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ( $objCatalog === null ) {

            return null;
        }

        $this->setFields( $objCatalog->id );

        $this->arrCatalog = $this->parseCatalog( $objCatalog->row() );
    }


    protected function setFields( $intPid ) {

        $objFields = CatalogFieldModel::findByPid( $intPid );

        if ( $objFields === null ) {

            return null;
        }

        while ( $objFields->next() ) {

            $this->arrFields[ $objFields->fieldname ] = $this->parseField( $objFields->row() );
        }
    }
}