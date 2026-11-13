<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class ListingModule extends Listing
{
    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_module';
    // Seit v3 heißt das Hüllen-Template mod_listing_table (mod_listing gibt es nicht mehr)
    protected $strTemplate = 'mod_listing_table';
}