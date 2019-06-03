<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Model\DynModel;


class ModelWizard {


    protected $objModel = null;


    public function __construct( $strTable ) {

        if ( isset( $GLOBALS['TL_MODELS'][ $strTable ] ) ) {

            $this->objModel = new $GLOBALS['TL_MODELS'][ $strTable ]();

            return null;
        }

        $objDynModel = new DynModel();
        $objDynModel->createDynTable( $strTable );
        $this->objModel = $objDynModel;
    }


    public function getModel() {

        return $this->objModel;
    }
}