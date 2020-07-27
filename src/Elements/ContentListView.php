<?php

namespace Alnv\ContaoCatalogManagerBundle\Elements;

class ContentListView extends \Alnv\ContaoCatalogManagerBundle\Modules\Listing {

    protected $strKey = 'id';
    protected $arrOptions = [];
    protected $strTable = 'tl_content';
    protected $strTemplate = 'ce_listview';

    public function generate() {

        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['CTE']['listview'][0]) . ' ###';
            $objTemplate->id = $this->id;
            return $objTemplate->parse();
        }

        if ($this->customTpl != '' && TL_MODE == 'FE') {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }
}