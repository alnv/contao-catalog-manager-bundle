<?php

namespace Alnv\ContaoCatalogManagerBundle\Elements;

use Contao\ContentElement;
use Contao\ModuleModel;

class ContentComponent extends ContentElement
{

    protected $strTemplate = 'ce_component';

    public function generate()
    {

        return parent::generate();
    }

    protected function compile()
    {

        $objModule = ModuleModel::findByPk($this->module);
        if ($objModule == null) {
            return null;
        }

        global $objPage;

        $this->Template->page = $objPage->id;
        $this->Template->detailModule = ($objModule->cmMaster && $objModule->cmMasterModule) ? $objModule->cmMasterModule : '';
        $this->Template->active = $_GET['auto_item'] && (!$objModule->cmMasterPage || $this->cmMasterPage == $objPage->id);
    }
}