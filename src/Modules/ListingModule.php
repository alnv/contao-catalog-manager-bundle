<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

class ListingModule extends Listing
{

    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_module';
    protected $strTemplate = 'mod_listing';
}