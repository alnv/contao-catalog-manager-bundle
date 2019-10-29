<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;
use Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder;


class ListingModule extends \Module {


    protected $arrOptions = [];
    protected $strTemplate = 'mod_listing';


    public function generate() {

        if ( \System::getContainer()->get( 'request_stack' )->getCurrentRequest()->get('_scope') == 'backend' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . utf8_strtoupper( 'TODO' ) . ' ###';

            return $objTemplate->parse();
        }

        if ( !$this->cmTable ) {

            return null;
        }

        if ( \Input::get('auto_item') && $this->cmMasterModule ) {

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
        $this->setMasterPage();
        $this->setPagination();
        $this->setDistance();

        $objListing = new Listing( $this->cmTable, $this->arrOptions );

        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }


    protected function setDistance() {

        if ( !$this->cmRadiusSearch ) {

            return null;
        }

        $objRoleResolver = RoleResolver::getInstance( $this->cmTable );
        $arrGeoCodingFields = $objRoleResolver->getGeoCodingFields();

        if ( empty( $arrGeoCodingFields ) ) {

            return null;
        }

        $arrAddress = [
            'street' => Toolkit::getValueFromUrl( \Input::get('street') ),
            'streetNumber' => Toolkit::getValueFromUrl( \Input::get('streetNumber') ),
            'zip' => Toolkit::getValueFromUrl( \Input::get('zip') ),
            'city' => Toolkit::getValueFromUrl( \Input::get('city') ),
            'state' => Toolkit::getValueFromUrl( \Input::get('state') ),
            'country' => Toolkit::getValueFromUrl( \Input::get('country') )
        ];

        $objAddressBuilder = new AddressBuilder( $arrAddress );
        $strAddress = $objAddressBuilder->getAddress();
        $strRadius = Toolkit::getValueFromUrl( \Input::get('radius') ) ?: 50;

        if ( !$strAddress ) {

            return null;
        }

        $objGeoCoding = new \Alnv\ContaoGeoCodingBundle\Library\GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress( 'google-geocoding', $strAddress );

        if ( $arrGeoCoding !== null ) {

            $this->arrOptions['distance'] = [

                'latCord' => $arrGeoCoding['latitude'],
                'lngCord' => $arrGeoCoding['longitude'],
                'latField' => $arrGeoCodingFields['latitude'],
                'lngField' => $arrGeoCodingFields['longitude']
            ];

            $this->arrOptions['having'] = '_distance <= ' . floatval( $strRadius );
        }
    }


    protected function setFilter() {

        if ( !$this->cmFilter ) {

            return null;
        }

        $this->arrOptions['column'] = explode( ';', \StringUtil::decodeEntities( $this->cmColumn ) );
        $this->arrOptions['value'] = explode( ';', \StringUtil::decodeEntities( $this->cmValue ) );
    }


    protected function setOrder() {

        // @todo module settings
        // dyn by input
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
}