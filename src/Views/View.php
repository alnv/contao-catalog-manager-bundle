<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;


abstract class View extends \Controller {


    protected $strTable = null;
    protected $arrOptions = [];
    protected $dcaExtractor = null;

    protected $blnMaster = false;
    protected $arrMasterPage = null;


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

        // @todo improve option setter

        $this->arrOptions['id'] = (int) $arrOptions['id'];
        $this->arrOptions['alias'] = $arrOptions['alias'];
        $this->arrOptions['limit'] = (int) $arrOptions['limit'] ?: 0;
        $this->arrOptions['offset'] = (int) $arrOptions['offset'] ?: 0;
        $this->arrOptions['order'] = $arrOptions['order'] ?: $this->dcaExtractor->getOrderBy();

        if ( $arrOptions['column'] ) {

            $this->arrOptions['column'] = $arrOptions['column'];
        }

        if ( $arrOptions['value'] ) {

            $this->arrOptions['value'] = $arrOptions['value'];
        }

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

        $this->arrMasterPage = $arrOptions['masterPage'];
    }


    protected function parseEntity( $arrEntity, &$arrReturn = [] ) {

        // @todo improve parser -> &$arrReturn ?

        $arrRow = [];
        $arrRow['origin'] = [];

        if ( $this->arrMasterPage ) {

            $arrRow['masterUrl'] = \Controller::generateFrontendUrl( $this->arrMasterPage, $arrEntity['alias'] ? '/' . $arrEntity['alias'] : '' ); // @todo make alias interchangeable
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


    protected function parseField( $varValue, $strField, $arrValues ) {

        if ( $varValue === '' || $varValue === null ) {

            return $varValue;
        }

        $arrField = $this->dcaExtractor->getField( $strField );

        if ( !isset( $arrField['inputType'] ) ) {

            return $varValue;
        }

        switch ( $arrField['inputType'] ) {

            case 'text':

                // @todo multiple
                // @todo date

                return $varValue;

                break;

            case 'checkbox':
            case 'select':
            case 'radio':

                // @todo multiple
                // @todo get clean option
                if ( isset( $arrField['eval']['multiple'] ) && $arrField['eval']['multiple'] == true ) {

                    //
                }

                return $varValue;

                break;

            case 'fileTree':

                $strSizeId = null;

                if ( isset( $arrField['eval']['imageSize'] ) && $arrField['eval']['imageSize'] ) {

                    $strSizeId = $arrField['eval']['imageSize'];
                }

                if ( isset( $arrField['eval']['isImage'] ) && $arrField['eval']['isImage'] == true ) {

                    return \Alnv\ContaoCatalogManagerBundle\Helper\Image::getImage( $varValue, $strSizeId );
                }

                // @todo files
                return [];

                break;

            case 'pageTree':

                // @todo parse url

                return '';

                break;
        }

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