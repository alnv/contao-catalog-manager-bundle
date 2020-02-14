<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;


abstract class View extends \Controller {


    protected $strTable = null;
    protected $arrOptions = [];
    protected $arrEntities = [];
    protected $dcaExtractor = null;
    protected $arrMasterPage = null;


    public function __construct( $strTable, $arrOptions = [] ) {

        $this->strTable = $strTable;
        $this->initializeDataContainer();
        $this->dcaExtractor = new DcaExtractor( $strTable );

        foreach ( $arrOptions as $strName => $varValue ) {

            switch ( $strName ) {

                case 'id':

                    $this->arrOptions['id'] = (int) $varValue;

                    break;

                case 'alias':

                    $this->arrOptions['alias'] = $varValue;

                    break;

                case 'masterPage':

                    $objPage = \PageModel::findByPk( $varValue );

                    if ( $objPage !== null ) {

                        $this->arrMasterPage = $objPage->row();
                        $this->arrOptions['masterPage'] = true;
                    }

                    break;

                case 'limit':

                    $this->arrOptions['limit'] = (int) $varValue;

                    break;

                case 'offset':

                    $this->arrOptions['offset'] = (int) $varValue;

                    break;

                case 'pagination':

                    $this->arrOptions['pagination'] = $varValue;

                    break;

                case 'distance':

                    $this->arrOptions['distance'] = $varValue;

                    break;

                case 'having':

                    $this->arrOptions['having'] = $varValue;

                    break;

                case 'order':

                    $this->arrOptions['order'] = $varValue ?: $this->dcaExtractor->getOrderBy();

                    if ( !$this->arrOptions['order'] ) {

                        unset( $this->arrOptions['order'] );
                    }

                    break;

                case 'column':

                    if ( is_array( $varValue ) && !empty( $varValue ) ) {

                        $this->arrOptions['column'] = $varValue;
                    }

                    break;

                case 'value':

                    if ( is_array( $varValue ) && !empty( $varValue ) ) {

                        $this->arrOptions['value'] = $varValue;
                    }

                    break;

                case 'groupBy':

                    $this->arrOptions['groupBy'] = $varValue;

                    break;

                case 'groupByHl':

                    $this->arrOptions['groupByHl'] = $varValue;

                    break;

                case 'template':

                    $this->arrOptions['template'] = $varValue;

                    break;

                case 'language':

                    $this->arrOptions['language'] = $varValue;

                    break;
            }
        }

        $this->paginate();

        parent::__construct();
    }


    protected function paginate() {

        if ( !$this->arrOptions['pagination'] && !\Input::get('reload') ) {

            return null;
        }

        $arrOptions = $this->getModelOptions();
        $numTotal = 0;
        $arrOptions['limit'] = 0;
        $arrOptions['offset'] = 0;

        $objModel = new ModelWizard( $this->strTable );
        $objModel = $objModel->getModel();
        $objTotal = $objModel->findAll($arrOptions);

        if ( $objTotal !== null ) {

            $numTotal = $objTotal->count();
        }

        if ( !$numTotal ) {

            return null;
        }

        if ( \Input::get('reload') ) { // vue reload

            $intOffset = (int) \Input::get('reload') + 1;
            $intLimit = $this->arrOptions['limit'] * $intOffset;

            if ( $intLimit > $numTotal ) {

                $intLimit = $numTotal;
            }

            $this->arrOptions['offset'] = 0;
            $this->arrOptions['limit'] = $intLimit;

            return null;
        }

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


    protected function initializeDataContainer() {

        $objApplication = new Application();
        $objApplication->initializeDataContainerArrayByTable( $this->strTable );

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ] ) ) {

            \Controller::loadDataContainer( $this->strTable );
        }
    }


    protected function getModelOptions() {

        $arrReturn = [];
        $arrOptions = [ 'limit', 'offset', 'pagination', 'order', 'column', 'value', 'distance', 'having', 'language' ];

        foreach ( $arrOptions as $strOption ) {

            if ( isset( $this->arrOptions[ $strOption ] ) ) {

                $arrReturn[ $strOption ] = $this->arrOptions[ $strOption ];
            }
        }

        if ( isset( $GLOBALS['TL_HOOKS']['getModelOptions'] ) && is_array( $GLOBALS['TL_HOOKS']['getModelOptions'] ) ) {

            foreach ( $GLOBALS['TL_HOOKS']['getModelOptions'] as $arrCallback ) {

                $this->import( $arrCallback[0] );
                $arrReturn = $this->{$arrCallback[0]}->{$arrCallback[1]}( $arrReturn, $this->strTable, $this->arrOptions );
            }
        }

        return $arrReturn;
    }


    protected function parseEntity( $arrEntity ) {

        $arrRow = [];
        $arrRow['origin'] = [];
        $arrRow['_table'] = $this->strTable;

        if ( $this->arrOptions['masterPage'] ) {

            $arrRow['masterUrl'] = Toolkit::parseDetailLink( $this->arrMasterPage, $arrEntity['alias'] ); // @todo make alias changeable
        }

        foreach ( $arrEntity as $strField => $varValue ) {

            $strParsedValue = $this->parseField( $varValue, $strField, $arrEntity );

            if ( $strParsedValue !== $varValue ) {

                $arrRow['origin'][ $strField ] = $varValue;
            }

            $arrRow[ $strField ] = $strParsedValue;
        }

        $arrRow['roleResolver'] = function () use ( $arrRow ) {

            return \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance( $this->strTable, $arrRow );
        };

        $arrRow['shareButtons'] = function () use ( $arrRow ) {

            return ( new \Alnv\ContaoCatalogManagerBundle\Library\ShareButtons( $arrRow ) )->getShareButtons();
        };

        $arrRow['iCalendarUrl'] = function () use ( $arrRow ) {

            return ( new \Alnv\ContaoCatalogManagerBundle\Library\ICalendar( $arrRow ) )->getICalendarUrl();
        };

        if ( $this->arrOptions['template'] ) {

            $objTemplate = new \FrontendTemplate( $this->arrOptions['template'] );
            $objTemplate->setData( $arrRow );
            $arrRow['template'] =  $objTemplate->parse();
        }

        if ( $this->arrOptions['groupBy'] ) {

            $strGroup = $arrEntity[ $this->arrOptions['groupBy'] ];

            if ( !isset( $this->arrEntities[ $strGroup ] ) ) {

                $this->arrEntities[ $strGroup ] = [
                    'headline' => $arrRow[ $this->arrOptions['groupBy'] ],
                    'hl' => $this->arrOptions['groupByHl'],
                    'entities' => []
                ];
            }

            $this->arrEntities[ $strGroup ]['entities'][] = $arrRow;
        }

        else {

            $this->arrEntities[] = $arrRow;
        }

        return $arrEntity;
    }


    protected function parseField( $varValue, $strField, $arrValues ) {

        return Toolkit::parseCatalogValue( $varValue, \Widget::getAttributesFromDca( $this->dcaExtractor->getField( $strField ), $strField, $varValue, $strField, $this->strTable ), $arrValues );
    }


    protected function getPageNumber() {

        return (int) \Input::get( 'page_e' . $this->arrOptions['id'] );
    }


    public function getPagination() {

        if ( !$this->arrOptions['pagination'] ) {

            return '';
        }

        $objPagination = new \Pagination( $this->arrOptions['total'], $this->arrOptions['limit'], \Config::get('maxPaginationLinks'), 'page_e' . $this->arrOptions['id'] );

        return $objPagination->generate("\n  ");
    }


    public function getEntities() {

        if ( isset( $GLOBALS['TL_HOOKS']['parseViewEntities'] ) && is_array( $GLOBALS['TL_HOOKS']['parseViewEntities'] ) ) {

            foreach ( $GLOBALS['TL_HOOKS']['parseViewEntities'] as $arrCallback ) {

                $this->import( $arrCallback[0] );
                $this->{$arrCallback[0]}->{$arrCallback[1]}( $this->arrEntities, $this );
            }
        }

        return $this->arrEntities;
    }


    abstract public function parse();
}