<?php

namespace Alnv\ContaoCatalogManagerBundle\Elements;

use Alnv\ContaoCatalogManagerBundle\Helper\Mode;
use Alnv\ContaoCatalogManagerBundle\Modules\Listing;
use Contao\BackendTemplate;

class ContentListView extends Listing
{

    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_content';
    protected $strTemplate = 'ce_listview';

    public function generate()
    {

        if (Mode::get() == 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['CTE']['listview'][0]) . ' ###';
            $objTemplate->id = $this->id;
            return $objTemplate->parse();
        }

        if ($this->customTpl != '' && Mode::get() == 'FE') {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }
}