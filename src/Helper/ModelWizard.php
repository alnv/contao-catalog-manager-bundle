<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;

class ModelWizard {

    protected $objModel = null;

    public function __construct( $strTable ) {

        $strModel = \Model::getClassFromTable( $strTable );

        if ( $strModel && class_exists( $strModel ) ) {

            $this->objModel = new $strModel();

            return null;
        }

        if ( isset( $GLOBALS['CM_MODELS'][ $strTable ] ) && class_exists( $GLOBALS['CM_MODELS'][ $strTable ] ) ) {

            $objMultilingualDynModel = new $GLOBALS['CM_MODELS'][ $strTable ]();
            $objMultilingualDynModel->createDynTable( $strTable );
            $this->objModel = $objMultilingualDynModel;

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