<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Views\Listing;


class ListingModule extends \Module {


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

        $arrSettings = [
            'template' => $this->cmTemplate,
            'id' => $this->id
        ];
        $this->setGroup( $arrSettings );
        $this->setFilter( $arrSettings );
        $this->setMasterPage( $arrSettings );
        $this->setPagination( $arrSettings );
        $objListing = new Listing( $this->cmTable, $arrSettings );
        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }


    protected function setFilter( &$arrSettings ) {

        if ( $this->cmFilter ) {

            $arrSettings['column'] = explode( ',', $this->cmColumn );
            $arrSettings['value'] = explode( ',', $this->cmValue );
        }
    }


    protected function setPagination( &$arrSettings ) {

        if ( $this->cmPagination ) {

            $arrSettings['pagination'] = $this->cmPagination ? true: false;
        }

        if ( $this->cmLimit ) {

            $arrSettings['limit'] = $this->cmLimit;
        }

        if ( $this->cmOffset ) {

            $arrSettings['offset'] = $this->cmOffset;
        }
    }


    protected function setGroup( &$arrSettings ) {

        if ( $this->cmGroupBy ) {

            $arrSettings['groupBy'] = $this->cmGroupBy;
        }

        if ( $this->cmGroupByHl ) {

            $arrSettings['groupByHl'] = $this->cmGroupByHl;
        }
    }


    protected function setMasterPage( &$arrSettings ) {

        if ( $this->cmMaster ) {

            if ( $this->cmMasterPage ) {

                $objPage = \PageModel::findByPk( $this->cmMasterPage );

                if ( $objPage !== null ) {

                    $arrSettings['masterPage'] = $objPage->row();
                }
            }
        }
    }
}