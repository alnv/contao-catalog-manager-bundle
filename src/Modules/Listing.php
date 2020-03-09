<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;

use BackendTemplate;
use System;

class Listing extends \Module {

    protected $arrOptions = [];

    public function generate() {

        if ( System::getContainer()->get( 'request_stack' )->getCurrentRequest()->get('_scope') == 'backend' ) {

            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . utf8_strtoupper( $GLOBALS['TL_LANG']['FMD'][$this->type] ) . ' ###';

            return $objTemplate->parse();
        }

        if ( !$this->cmTable ) {

            return null;
        }

        if ( \Input::get('auto_item') && $this->cmMasterModule ) { // @todo impl formModule

            return \Controller::getFrontendModule( $this->cmMasterModule );
        }

        return parent::generate();
    }

    protected function compile() {

        $this->arrOptions = [
            'template' => $this->cmTemplate,
            'id' => $this->id
        ];

        $this->setOrder();
        $this->setGroup();
        $this->setFilter();
        $this->setFormPage();
        $this->setDistance();
        $this->setMasterPage();
        $this->setPagination();

        // @todo check visibility by data container >hasVisibilityFields<
        // @todo impl optional visibility parameter
        // @todo add visibility query in View Class

        $objListing = new \Alnv\ContaoCatalogManagerBundle\Views\Listing( $this->cmTable, $this->arrOptions );
        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }

    protected function setDistance() {

        if ( !$this->cmRadiusSearch ) {

            return false;
        }

        $objRoleResolver = RoleResolver::getInstance( $this->cmTable );
        $arrGeoCodingFields = $objRoleResolver->getGeoCodingFields();

        if ( empty( $arrGeoCodingFields ) ) {

            return false;
        }

        $arrAddress = [

            'street' => Toolkit::getValueFromUrl( \Input::get('street') ),
            'streetNumber' => Toolkit::getValueFromUrl( \Input::get('streetNumber') ),
            'zip' => Toolkit::getValueFromUrl( \Input::get('zip') ),
            'city' => Toolkit::getValueFromUrl( \Input::get('city') ),
        ];

        if ( empty( array_filter( $arrAddress ) ) ) {

            return false;
        }

        $arrAddress['state'] = Toolkit::getValueFromUrl( \Input::get('state') );
        $arrAddress['country'] = Toolkit::getValueFromUrl( \Input::get('country') );
        $objAddressBuilder = new AddressBuilder( $arrAddress );
        $strAddress = $objAddressBuilder->getAddress();
        $strRadius = Toolkit::getValueFromUrl( \Input::get('radius') ) ?: 15;
        $objGeoCoding = new \Alnv\ContaoGeoCodingBundle\Library\GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress( 'google-geocoding', $strAddress );

        if ( $arrGeoCoding !== null ) {

            $this->arrOptions['distance'] = [
                'latCord' => $arrGeoCoding['latitude'],
                'lngCord' => $arrGeoCoding['longitude'],
                'latField' => $arrGeoCodingFields['latitude'],
                'lngField' => $arrGeoCodingFields['longitude']
            ];

            $this->arrOptions['having'] = '_distance < ' . (int) $strRadius;
            $this->arrOptions['order'] = '_distance ASC';

            return true;
        }

        return false;
    }

    protected function setFilter() {

        if ( !$this->cmFilter ) {
            return null;
        }

        switch ( $this->cmFilterType ) {
            case 'wizard':
                \Controller::loadDataContainer($this->cmTable);
                $arrQueries = Toolkit::convertComboWizardToModelValues( $this->cmWizardFilterSettings, $GLOBALS['TL_DCA'][$this->cmTable]['config']['_table'] );
                $this->arrOptions['column'] = $arrQueries['column'];
                $this->arrOptions['value'] = $arrQueries['value'];
                break;

            case 'expert':
                $this->cmValue =  \Controller::replaceInsertTags( $this->cmValue );
                $this->arrOptions['column'] = explode( ';', \StringUtil::decodeEntities( $this->cmColumn ) );
                $this->arrOptions['value'] = explode( ';', \StringUtil::decodeEntities( $this->cmValue ) );
                if ( ( is_array( $this->arrOptions['value'] ) && !empty( $this->arrOptions['value'] ) ) ) {
                    $intIndex = -1;
                    $this->arrOptions['value'] = array_filter( $this->arrOptions['value'], function ( $strValue ) use ( &$intIndex ) {
                        $intIndex = $intIndex + 1;
                        if ( $strValue === '' || $strValue === null ) {
                            unset( $this->arrOptions['column'][ $intIndex ] );
                            return false;
                        }
                        return true;
                    });
                    if ( empty( $this->arrOptions['value'] ) ) {
                        unset( $this->arrOptions['value'] );
                        unset( $this->arrOptions['column'] );
                    }
                }
                break;
        }
    }

    protected function setOrder() {

        if ( $this->cmOrder ) {

            $strOrder = Toolkit::getOrderByStatementFromArray( \Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit::decodeJson( $this->cmOrder, [
                'option' => 'field',
                'option2' => 'order'
            ]));

            $this->arrOptions['order'] = $strOrder;
        }

        if ( is_array( \Input::get('order') ) && !empty( \Input::get('order') ) ) {

            $this->arrOptions['order'] = Toolkit::getOrderByStatementFromArray( \Input::get('order') );
        }
    }

    protected function setPagination() {

        if ( $this->cmPagination ) {

            $this->arrOptions['pagination'] = $this->cmPagination ? true: false;
        }

        if ( $this->cmLimit ) {

            $this->arrOptions['limit'] = $this->cmLimit;
        }

        if ( $this->cmOffset ) {

            $this->arrOptions['offset'] = $this->cmOffset;
        }
    }

    protected function setGroup() {

        if ( $this->cmGroupBy ) {

            $this->arrOptions['groupBy'] = $this->cmGroupBy;
        }

        if ( $this->cmGroupByHl ) {

            $this->arrOptions['groupByHl'] = $this->cmGroupByHl;
        }
    }

    protected function setMasterPage() {

        if ( !$this->cmMaster || !$this->cmMasterPage ) {

            return null;
        }

        $this->arrOptions['masterPage'] = $this->cmMasterPage;
    }

    protected function setFormPage() {

        if ( !$this->cmForm || !$this->cmFormPage ) {

            return null;
        }

        $this->arrOptions['formPage'] = $this->cmFormPage;
    }
}