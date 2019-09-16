<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;


abstract class View extends \Controller {


    protected $strTable = null;
    protected $arrOptions = [];
    protected $arrEntities = [];
    protected $blnMaster = false;
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

                case 'order':

                    $this->arrOptions['order'] = $varValue ?: $this->dcaExtractor->getOrderBy();

                    if ( !$this->arrOptions['order'] ) {

                        unset( $this->arrOptions['order'] );
                    }

                    break;

                case 'column':

                    if ( is_array( $varValue ) && !empty( is_array( $varValue ) ) ) {

                        $this->arrOptions['column'] = $varValue;
                    }

                    break;

                case 'value':

                    if ( is_array( $varValue ) && !empty( is_array( $varValue ) ) ) {

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
            }
        }

        $this->paginate();

        parent::__construct();
    }


    protected function paginate() {

        if ( !$this->arrOptions['pagination'] ) {

            return null;
        }

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


    protected function initializeDataContainer() {

        $objApplication = new Application();
        $objApplication->initializeDataContainerArrayByTable( $this->strTable );

        if ( !isset( $GLOBALS['TL_DCA'][ $this->strTable ] ) ) {

            \Controller::loadDataContainer( $this->strTable );
        }
    }


    protected function getModelOptions() {

        $arrReturn = [];
        $arrOptions = [ 'limit', 'offset', 'order', 'column', 'value' ];

        foreach ( $arrOptions as $strOption ) {

            if ( isset( $this->arrOptions[ $strOption ] ) ) {

                $arrReturn[ $strOption ] = $this->arrOptions[ $strOption ];
            }
        }

        return $arrReturn;
    }


    protected function parseEntity( $arrEntity ) {

        $arrRow = [];
        $arrRow['origin'] = [];

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

        if ( $varValue === '' || $varValue === null ) {

            return $varValue;
        }

        $arrField = \Widget::getAttributesFromDca( $this->dcaExtractor->getField( $strField ), $strField, $varValue, $strField, $this->strTable );

        if ( !isset( $arrField['type'] ) ) {

            return $varValue;
        }

        switch ( $arrField['type'] ) {

            case 'text':

                return $arrField['value'];

                break;

            case 'checkbox':
            case 'select':
            case 'radio':

                $varValue = !is_array( $arrField['value'] ) ? [ $arrField['value'] ] : $arrField['value'];

                return $this->getSelectedOptions( $varValue, $arrField['options'] );

                break;

            case 'fileTree':

                $strSizeId = null;

                if ( isset( $arrField['imageSize'] ) && $arrField['imageSize'] ) {

                    $strSizeId = $arrField['imageSize'];
                }

                if ( isset( $arrField['isImage'] ) && $arrField['isImage'] == true ) {

                    return \Alnv\ContaoCatalogManagerBundle\Helper\Image::getImage( $varValue, $strSizeId );
                }

                return []; // @todo files

                break;

            case 'pageTree':

                return ''; // @todo parse url

                break;
        }

        return $arrField['value'];
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


    protected function getSelectedOptions( $arrValues, $arrOptions ) {

        $arrReturn = [];

        if ( !is_array( $arrOptions ) || !is_array( $arrValues ) ) {

            return [];
        }

        foreach ( $arrOptions as $arrValue ) {

            if ( in_array( $arrValue['value'], $arrValues ) ) {

                $arrReturn[] = $arrValue;
            }
        }

        return $arrReturn;
    }


    abstract public function parse();
}