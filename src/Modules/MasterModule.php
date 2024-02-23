<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\System;

class MasterModule extends Module
{

    protected $strTemplate = 'mod_master';

    public function generate()
    {

        if (System::getContainer()->get('request_stack')->getCurrentRequest()->get('_scope') == 'backend') {

            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['FMD']['master'][0]) . ' ###';

            return $objTemplate->parse();
        }

        if (!$this->cmTable) {
            return null;
        }

        return parent::generate();
    }

    protected function compile(): void
    {

        $objMaster = new Master($this->cmTable, [
            'alias' => Input::get('auto_item'),
            'template' => $this->cmTemplate,
            'ignoreVisibility' => (bool)$this->cmIgnoreVisibility,
            'id' => $this->id
        ]);

        $arrMaster = $objMaster->parse();
        if (empty($arrMaster)) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        $this->Template->entities = $arrMaster;
    }
}