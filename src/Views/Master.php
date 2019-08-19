<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;


class Master extends View {


    public function parse() {

        $arrReturn = [];
        $objModel = new ModelWizard( $this->strTable );
        $objModel = $objModel->getModel();
        $objEntity = $objModel->findByIdOrAlias( $this->arrOptions['alias'], $this->arrOptions );

        if ( $objEntity !== null ) {

            $this->parseEntity( $objEntity->row(), $arrReturn );
        }

        return $arrReturn;
    }
}