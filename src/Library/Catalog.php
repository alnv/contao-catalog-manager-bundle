<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;


class Catalog extends CatalogWizard {


    protected $arrFields = [];
    protected $arrCatalog = [];
    protected $strIdentifier = null;


    public function __construct( $strIdentifier ) {

        $this->strIdentifier = $strIdentifier;
        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ( $objCatalog === null ) {

            return null;
        }

        $this->getDefaultFields();
        $this->arrCatalog = $this->parseCatalog( $objCatalog->row() );
        $objFields = CatalogFieldModel::findByPid( $this->arrCatalog['id'] ); //@todo visible only and order

        if ( $objFields === null ) {

            return null;
        }

        while ( $objFields->next() ) {

            $arrField = $this->parseField( $objFields->row() );

            if ( $arrField === null ) {

                continue;
            }

            $this->arrFields[ $objFields->fieldname ] = $arrField;
        }
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