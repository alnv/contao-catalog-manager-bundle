<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;


class Listing extends View {


    public function parse() {

        $objModel = new ModelWizard( $this->strTable );
        $objModel = $objModel->getModel();
        $objEntities = $objModel->findAll();
        $arrReturn = [];

        if ( $objEntities !== null ) {

            while ( $objEntities->next() ) {

                $arrReturn[] = $this->parseEntity( $objEntities->row() );
            }
        }

        return $arrReturn;
    }
}