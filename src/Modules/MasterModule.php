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
use Contao\StringUtil;

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

        $strAlias = ($_GET['auto_item'] ?? '') ? Input::get('auto_item') : '';

        $arrOptions = [
            'id' => $this->id,
            'alias' => $strAlias,
            'template' => $this->cmTemplate,
            'ignoreVisibility' => (bool)$this->cmIgnoreVisibility
        ];

        $this->setFilter($arrOptions);

        $objMaster = new Master($this->cmTable, $arrOptions);
        $arrMaster = $objMaster->parse();

        if (empty($arrMaster)) {
            throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
        }

        $this->Template->entities = $arrMaster;
    }

    protected function setFilter(&$arrOptions)
    {

        if (!$this->cmFilter) {
            return null;
        }

        switch ($this->cmFilterType) {
            case 'wizard':

                Controller::loadDataContainer($this->cmTable);

                $arrQueries = Toolkit::convertComboWizardToModelValues($this->cmWizardFilterSettings, $GLOBALS['TL_DCA'][$this->cmTable]['config']['_table']);

                if (!empty($arrQueries) && !empty($arrQueries['column'])) {
                    $arrOptions['column'] = $arrQueries['column'];
                    $arrOptions['value'] = $arrQueries['value'];
                }

                break;
            case 'expert':

                $this->cmValue = Controller::replaceInsertTags($this->cmValue);

                $arrOptions['column'] = explode(';', StringUtil::decodeEntities($this->cmColumn));
                $arrOptions['value'] = explode(';', StringUtil::decodeEntities($this->cmValue));

                if ((is_array($arrOptions['value']) && !empty($arrOptions['value']))) {
                    $intIndex = -1;
                    $arrOptions['value'] = array_filter($arrOptions['value'], function ($strValue) use (&$intIndex) {
                        $intIndex = $intIndex + 1;
                        if ($strValue === '' || $strValue === null) {
                            return false;
                        }
                        return true;
                    });
                    if (empty($arrOptions['value'])) {
                        unset($arrOptions['value']);
                        unset($arrOptions['column']);
                    }
                }

                break;
        }
    }
}