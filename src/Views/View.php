<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
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

        $this->arrOptions['id'] = (int) $arrOptions['id'];
        $this->arrOptions['alias'] = $arrOptions['alias'];
        $this->arrOptions['limit'] = (int) $arrOptions['limit'] ?: 0;
        $this->arrOptions['offset'] = (int) $arrOptions['offset'] ?: 0;
        $this->arrOptions['order'] = $arrOptions['order'] ?: $this->dcaExtractor->getOrderBy();

        if ( !$this->arrOptions['order'] ) {

            unset( $this->arrOptions['order'] );
        }

        if ( $arrOptions['template'] ) {

            $this->arrOptions['template'] = $arrOptions['template'];
        }

        if ( $arrOptions['groupBy'] ) {

            $this->arrOptions['groupBy'] = $arrOptions['groupBy'];
            $this->arrOptions['groupByHl'] = $arrOptions['groupByHl'];
        }

        if ( $arrOptions['pagination'] ) {

            $objModel = new ModelWizard( $this->strTable );
            $objModel = $objModel->getModel();
            $numTotal = $objModel->countBy( [ 'id > ?' ], [ 0 ], $this->arrOptions );
            $numOffset = $this->arrOptions['offset'];

            if ( $this->arrOptions['offset'] ) {

                $numTotal -= $numOffset;
            }

            $numOffset = $this->getPageNumber();

            if ( $this->arrOptions['limit'] > 0 && $this->arrOptions['offset'] ) {

                $numOffset += round( $this->arrOptions['offset'] / $this->arrOptions['limit'] );
            }

            $this->arrOptions['offset'] = ( $numOffset - 1 ) * $this->arrOptions['limit'];
            $this->arrOptions['total'] = $numTotal;
        }
    }


    protected function parseEntity( $arrEntity, &$arrReturn = [] ) {

        $arrRow = [];
        $arrRow['origin'] = [];
        $arrRow['masterUrl'] = '';

        foreach ( $arrEntity as $strField => $varValue ) {

            $strParsedValue = $this->parseField( $varValue, $strField );

            if ( $strParsedValue !== $varValue ) {

                $arrRow['origin'][ $strField ] = $varValue;
            }

            $arrRow[ $strField ] = $strParsedValue;
        }

        if ( $this->arrOptions['template'] ) {

            $objTemplate = new \FrontendTemplate( $this->arrOptions['template'] );
            $objTemplate->setData( $arrRow );
            $arrRow['template'] =  $objTemplate->parse();
        }

        if ( $this->arrOptions['groupBy'] ) {

            $strGroup = $arrEntity[ $this->arrOptions['groupBy'] ];

            if ( !isset( $arrReturn[ $strGroup ] ) ) {

                $arrReturn[ $strGroup ] = [
                    'headline' => $arrRow[ $this->arrOptions['groupBy'] ],
                    'hl' => $this->arrOptions['groupByHl'],
                    'entities' => []
                ];
            }

            $arrReturn[ $strGroup ]['entities'][] = $arrRow;
        }

        else {

            $arrReturn[] = $arrRow;
        }

        return $arrEntity;
    }


    protected function parseField( $varValue, $strField ) {

        return $varValue;
    }


    protected function getPageNumber() {

        return (int) \Input::get( 'page_e' . $this->arrOptions['id'] );
    }


    public function getPagination() {

        if ( !$this->arrOptions['total'] ) {

            return '';
        }

        $objPagination = new \Pagination( $this->arrOptions['total'], $this->arrOptions['limit'], \Config::get('maxPaginationLinks'), 'page_e' . $this->arrOptions['id'] );

        return $objPagination->generate("\n  ");
    }
}