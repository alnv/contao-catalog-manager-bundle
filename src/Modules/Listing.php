<?php

namespace Alnv\ContaoCatalogManagerBundle\Modules;

use Alnv\ContaoCatalogManagerBundle\Helper\Mode;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Views\Listing as ViewListing;
use Alnv\ContaoGeoCodingBundle\Helpers\AddressBuilder;
use Alnv\ContaoGeoCodingBundle\Library\GeoCoding;
use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Hybrid;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;


class Listing extends Hybrid
{

    protected $objModel = null;

    public function generate()
    {

        if (System::getContainer()->get('request_stack')->getCurrentRequest()->get('_scope') == 'backend') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->title = $this->headline;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
            $objTemplate->wildcard = '### ' . strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]) . ' ###';
            return $objTemplate->parse();
        }

        if (!$this->cmTable) {
            return null;
        }

        if (Input::get('auto_item') && $this->cmMasterModule) {
            return Controller::getFrontendModule($this->cmMasterModule);
        }

        $this->strKey = $this->type;
        $this->typePrefix = $this->strTable == 'tl_module' ? 'mod_' : 'ce_';
        if ($this->customTpl && Mode::get() == 'FE') {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }

    public function setOptions()
    {

        $this->setOrder();
        $this->setGroup();
        $this->setFilter();
        $this->setFormPage();
        $this->setDistance();
        $this->setMasterPage();
        $this->setPagination();
        $this->setIgnoreVisibility();
        $this->setIgnoreFieldsFromParsing();
    }

    public function getOptions()
    {

        return $this->arrOptions;
    }

    public function getTable()
    {

        return $this->cmTable;
    }

    protected function compile()
    {

        $this->arrOptions = [
            'template' => $this->cmTemplate,
            'id' => $this->id
        ];

        $this->setOptions();
        $objListing = new ViewListing($this->cmTable, $this->arrOptions);

        $this->Template->rows = $objListing->countRows();
        $this->Template->entities = $objListing->parse();
        $this->Template->pagination = $objListing->getPagination();
    }

    protected function setIgnoreVisibility()
    {

        $this->arrOptions['ignoreVisibility'] = (bool)$this->cmIgnoreVisibility;
    }

    protected function setIgnoreFieldsFromParsing()
    {

        $this->arrOptions['ignoreFieldsFromParsing'] = StringUtil::deserialize($this->cmIgnoreFieldsFromParsing, true);
    }

    protected function setDistance()
    {

        if (!$this->cmRadiusSearch) {
            return false;
        }

        $objRoleResolver = RoleResolver::getInstance($this->cmTable);
        $arrGeoCodingFields = $objRoleResolver->getGeoCodingFields();

        if (empty($arrGeoCodingFields)) {
            return false;
        }

        $arrAddress = [
            'street' => Toolkit::getValueFromUrl(Input::get('street')),
            'streetNumber' => Toolkit::getValueFromUrl(Input::get('streetNumber')),
            'zip' => Toolkit::getValueFromUrl(Input::get('zip') ?: Input::get('postal')),
            'city' => Toolkit::getValueFromUrl(Input::get('city')),
        ];

        if (empty(array_filter($arrAddress))) {
            return false;
        }

        $arrAddress['state'] = Toolkit::getValueFromUrl(Input::get('state'));
        $arrAddress['country'] = Toolkit::getValueFromUrl(Input::get('country'));
        $objAddressBuilder = new AddressBuilder($arrAddress);
        $strAddress = $objAddressBuilder->getAddress();
        $strRadius = Toolkit::getValueFromUrl(Input::get('radius')) ?: 15;
        $objGeoCoding = new GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress('google-geocoding', $strAddress);

        if ($arrGeoCoding !== null) {

            $this->arrOptions['distance'] = [
                'latCord' => $arrGeoCoding['latitude'],
                'lngCord' => $arrGeoCoding['longitude'],
                'latField' => $arrGeoCodingFields['latitude'],
                'lngField' => $arrGeoCodingFields['longitude']
            ];

            $this->arrOptions['having'] = '_distance < ' . (int)$strRadius;
            $this->arrOptions['order'] = '_distance ASC';

            return true;
        }

        return false;
    }

    protected function setFilter()
    {

        if (!$this->cmFilter) {
            return null;
        }

        switch ($this->cmFilterType) {
            case 'wizard':
                Controller::loadDataContainer($this->cmTable);
                $arrQueries = Toolkit::convertComboWizardToModelValues($this->cmWizardFilterSettings, $GLOBALS['TL_DCA'][$this->cmTable]['config']['_table']);
                $this->arrOptions['column'] = $arrQueries['column'];
                $this->arrOptions['value'] = $arrQueries['value'];
                break;
            case 'expert':
                $this->cmValue = Toolkit::replaceInsertTags($this->cmValue);
                $this->arrOptions['column'] = explode(';', StringUtil::decodeEntities($this->cmColumn));
                $this->arrOptions['value'] = explode(';', StringUtil::decodeEntities($this->cmValue));
                if ((is_array($this->arrOptions['value']) && !empty($this->arrOptions['value']))) {
                    $intIndex = -1;
                    $this->arrOptions['value'] = array_filter($this->arrOptions['value'], function ($strValue) use (&$intIndex) {
                        $intIndex = $intIndex + 1;
                        if ($strValue === '' || $strValue === null) {
                            unset($this->arrOptions['column'][$intIndex]);
                            return false;
                        }
                        return true;
                    });
                    if (empty($this->arrOptions['value'])) {
                        unset($this->arrOptions['value']);
                        unset($this->arrOptions['column']);
                    }
                }
                break;
        }
    }

    protected function setOrder()
    {

        if ($this->cmOrder) {
            $strOrder = Toolkit::getOrderByStatementFromArray(Toolkit::decodeJson($this->cmOrder, [
                'option' => 'field',
                'option2' => 'order'
            ]));
            if ($strOrder) {
                $this->arrOptions['order'] = $strOrder;
            }
        }

        if ($this->cmFilter && ($this->cmWizardFilterSettings || $this->cmValue)) {
            $strFilterValues = $this->cmWizardFilterSettings ?: $this->cmValue;
            if (strpos($strFilterValues, 'LAST-ADDED-MASTER-VIEW-IDS') !== false) {
                $arrIds = Toolkit::getLastAddedByTypeAndTable('view-master', $this->cmTable);
                if (!$this->arrOptions['order']) {
                    $this->arrOptions['order'] = '';
                }
                if (!empty($arrIds)) {
                    $this->arrOptions['order'] .= ($this->arrOptions['order'] ? ',' : '') . ('FIELD(' . $this->cmTable . '.id,' . implode(',', $arrIds) . ')');
                }
            }
        }

        if (is_array(Input::get('order')) && !empty(Input::get('order'))) {
            $this->arrOptions['order'] = Toolkit::getOrderByStatementFromArray(Input::get('order'));
        }
    }

    protected function setPagination()
    {

        if ($this->cmPagination) {
            $this->arrOptions['pagination'] = $this->cmPagination ? true : false;
        }

        if ($this->cmLimit) {
            $this->arrOptions['limit'] = $this->cmLimit;
        }

        if ($this->cmOffset) {
            $this->arrOptions['offset'] = $this->cmOffset;
        }
    }

    protected function setGroup()
    {

        if ($this->cmGroupBy) {
            $this->arrOptions['groupBy'] = $this->cmGroupBy;
        }

        if ($this->cmGroupByHl) {
            $this->arrOptions['groupByHl'] = $this->cmGroupByHl;
        }
    }

    protected function setMasterPage()
    {

        if (!$this->cmMaster) {
            return null;
        }
        if (!$this->cmMasterPage) {
            global $objPage;
            $this->cmMasterPage = $objPage->id;
        }

        $this->arrOptions['masterPage'] = $this->cmMasterPage;
    }

    protected function setFormPage()
    {

        if (!$this->cmForm || !$this->cmFormPage) {
            return null;
        }

        $this->arrOptions['formPage'] = $this->cmFormPage;
    }
}