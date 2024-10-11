<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\System;

class MasterModule extends Module
{

    protected $strTemplate = 'mod_master';

    public function generate(): string
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
            return '';
        }

        return parent::generate();
    }

    protected function compile(): void
    {

        $strAlias = ($_GET['auto_item'] ?? '') ? Input::get('auto_item') : '';

        $arrOptions = [
            'id' => $this->id,
            'alias' => $strAlias,
            'template' => $this->cmTemplate,
            'ignoreVisibility' => (bool)$this->cmIgnoreVisibility
        ];

        foreach ($this->getFilter() as $strKey => $arrFilter) {

            if (empty($arrFilter)) {
                continue;
            }

            $arrOptions[$strKey] = $arrFilter;
        }

        $objMaster = new Master($this->cmTable, $arrOptions);
        $arrMaster = $objMaster->parse();

        if (empty($arrMaster)) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        $this->Template->entities = $arrMaster;
    }

    protected function getFilter(): array
    {

        $arrFilterOptions = [];

        if (!$this->cmFilter) {
            return $arrFilterOptions;
        }

        switch ($this->cmFilterType) {
            case 'wizard':
                Controller::loadDataContainer($this->cmTable);
                $arrQueries = Toolkit::convertComboWizardToModelValues($this->cmWizardFilterSettings, ($GLOBALS['TL_DCA'][$this->cmTable]['config']['_table'] ?? ''));
                $arrFilterOptions['column'] = $arrQueries['column'];
                $arrFilterOptions['value'] = $arrQueries['value'];
                break;
            case 'expert':
                foreach (Toolkit::convertExpertQueries(($this->cmColumn ?: ''), ($this->cmValue ?: '')) as $strKey => $strValue) {
                    $arrFilterOptions[$strKey] = $strValue;
                }
                break;
        }

        return $arrFilterOptions;
    }
}