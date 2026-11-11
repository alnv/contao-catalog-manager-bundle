<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\FrontendTemplate;
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
            $objTemplate->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . \strtoupper($GLOBALS['TL_LANG']['FMD']['master'][0] ?? '') . ' ###';

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

        // Performance: Der Layout-Hook (GetPageLayoutListener) hat diesen Eintrag bereits geparst.
        // Ohne Modul-Filter und ohne "Sichtbarkeit ignorieren" ist das Ergebnis identisch →
        // nur noch das Template rendern statt die Detailansicht ein zweites Mal zu laden und zu parsen.
        if ($arrMaster = $this->getMasterFromLayout($strAlias)) {
            $this->Template->entities = $arrMaster;
            return;
        }

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

    protected function getMasterFromLayout(string $strAlias): array
    {
        $arrContext = $GLOBALS['CM_MASTER_CONTEXT'] ?? null;
        $arrRow = $GLOBALS['CM_MASTER'] ?? null;

        if (!\is_array($arrContext) || !\is_array($arrRow) || empty($arrRow)) {
            return [];
        }

        if ($this->cmFilter || $this->cmIgnoreVisibility) {
            return [];
        }

        if (($arrContext['table'] ?? '') !== (string)$this->cmTable || ($arrContext['alias'] ?? '') !== $strAlias) {
            return [];
        }

        // Hooks, die View-Optionen/Entities pro Modul verändern, bekommen weiterhin den vollen Pfad
        if (!empty($GLOBALS['TL_HOOKS']['initializeView']) || !empty($GLOBALS['TL_HOOKS']['getModelOptions'])) {
            return [];
        }

        if ($this->cmTemplate) {
            $objTemplate = new FrontendTemplate($this->cmTemplate);
            $objTemplate->setData($arrRow);
            $arrRow['template'] = $objTemplate->parse();
        }

        return [$arrRow];
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