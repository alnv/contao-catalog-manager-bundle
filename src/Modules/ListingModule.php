<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Views\Listing;


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

        $this->setGroup();
        $this->setFilter();
        $this->setMasterPage();
        $this->setPagination();

        $objListing = new Listing( $this->cmTable, $this->arrOptions );

        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }


    protected function setFilter() {

        if ( $this->cmFilter ) {

            $this->arrOptions['column'] = explode( ',', $this->cmColumn );
            $this->arrOptions['value'] = explode( ',', $this->cmValue );
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

        if ( $this->cmMaster ) {

            if ( $this->cmMasterPage ) {

                $this->arrOptions['masterPage'] = $this->cmMasterPage;
            }
        }
    }
}