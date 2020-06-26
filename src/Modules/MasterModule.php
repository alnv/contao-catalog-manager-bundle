<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Views\Master;

class MasterModule extends \Module {

    protected $strTemplate = 'mod_master';

    public function generate() {

        if ( \System::getContainer()->get( 'request_stack' )->getCurrentRequest()->get('_scope') == 'backend' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . strtoupper( $GLOBALS['TL_LANG']['FMD']['master'] ) . ' ###';

            return $objTemplate->parse();
        }

        if ( !$this->cmTable ) {
            return null;
        }

        return parent::generate();
    }

    protected function compile() {

        $objMaster = new Master( $this->cmTable, [
            'alias' => \Input::get('auto_item'),
            'template' => $this->cmTemplate,
            'id' => $this->id
        ]);

        $arrMaster = $objMaster->parse();
        if (empty($arrMaster)) {
            throw new \CoreBundle\Exception\PageNotFoundException('Page not found: ' . \Environment::get('uri'));
        }

        $this->Template->entities = $arrMaster;
    }
}