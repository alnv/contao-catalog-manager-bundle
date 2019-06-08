<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;


class Listing extends View {


    public function parse() {

        $objModel = new ModelWizard( $this->strTable );
        $objModel = $objModel->getModel();
        $objEntities = $objModel->findAll($this->arrOptions);
        $arrReturn = [];

        if ( $objEntities !== null ) {

            while ( $objEntities->next() ) {

                $this->parseEntity( $objEntities->row(), $arrReturn );
            }
        }

        return $arrReturn;
    }
}