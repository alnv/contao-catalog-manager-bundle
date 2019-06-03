<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;


abstract class View extends \Controller {


    protected $strTable = null;
    protected $arrOptions = [];
    protected $dcaExtractor = null;


    public function __construct( $strTable, $arrOptions = [] ) {

        $this->setDataContainer( $strTable );
        $this->setOptions( $arrOptions );

        parent::__construct();
    }


    protected function setDataContainer( $strTable ) {

        $this->strTable = $strTable;

        $objApplication = new Application();
        $objApplication->initializeDataContainerArrayByTable( $this->strTable );

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ] ) ) {

            \Controller::loadDataContainer( $this->strTable );
        }

        $this->dcaExtractor = new DcaExtractor( $this->strTable );
    }


    protected function setOptions( $arrOptions ) {

        foreach ( $arrOptions as $strType => $varOption ) {

            if ( $varOption ) {

                $this->arrOptions[ $strType ] = $varOption;
            }
        }

        if ( !isset( $this->arrOptions['orderBy'] ) || empty( $this->arrOptions['orderBy'] ) ) {

            $this->arrOptions['orderBy'] = $this->dcaExtractor->getOrderBy();
        }
    }


    protected function parseEntity( $arrEntity ) {

        // @todo

        return $arrEntity;
    }
}