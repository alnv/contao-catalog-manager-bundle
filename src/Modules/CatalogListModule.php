<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Views\Listing;


class CatalogListModule extends \Module {


    protected $strTemplate = 'mod_catalog_list';


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

        return parent::generate();
    }


    protected function compile() {

        $objListing = new Listing( 'tl_product', [
            'template' => 'cm_listing_default',
            // 'groupBy' => 'type',
            // 'groupByHl' => 'h2',
            'limit' => 0,
            'offset' => 0,
            'id' => $this->id,
            // 'pagination' => true
        ]);

        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }
}