<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Library\Application;

abstract class View extends \Controller {

    public $arrFormPage = [];
    public $arrMasterPage = [];
    protected $strTable = null;
    protected $arrOptions = [];
    protected $arrEntities = [];
    protected $dcaExtractor = null;

    public function __construct( $strTable, $arrOptions = [] ) {

        $this->strTable = $strTable;
        $this->initializeDataContainer();
        $this->dcaExtractor = new \Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor($strTable);

        foreach ( $arrOptions as $strName => $varValue ) {
            switch ( $strName ) {
                case 'id':
                    $this->arrOptions['id'] = (int) $varValue;
                    break;

                case 'alias':
                    $this->arrOptions['alias'] = $varValue;
                    break;

                case 'isForm':
                    $this->arrOptions['isForm'] = (bool) $varValue;
                    break;

                case 'masterPage':
                    $objPage = \PageModel::findByPk($varValue);
                    if ($objPage !== null) {
                        $this->arrMasterPage = $objPage->row();
                        $this->arrOptions['masterPage'] = true;
                    }
                    break;

                case 'formPage':
                    $objPage = \PageModel::findByPk($varValue);
                    if ( $objPage !== null ) {
                        $this->arrFormPage = $objPage->row();
                        $this->arrOptions['formPage'] = true;
                    }
                    break;

                case 'limit':
                    $this->arrOptions['limit'] = (int) $varValue;
                    break;

                case 'fastMode':
                    $this->arrOptions['fastMode'] = $varValue ? true : false;
                    break;

                case 'offset':
                    $this->arrOptions['offset'] = (int) $varValue;
                    break;

                case 'pagination':
                    $this->arrOptions['pagination'] = $varValue;
                    break;

                case 'distance':
                    $this->arrOptions['distance'] = $varValue;
                    break;

                case 'having':
                    $this->arrOptions['having'] = $varValue;
                    break;

                case 'ignoreVisibility':
                    $this->arrOptions['ignoreVisibility'] = $varValue;
                    break;

                case 'order':
                    $this->arrOptions['order'] = $varValue ?: $this->dcaExtractor->getOrderBy();
                    if ( !$this->arrOptions['order'] ) {
                        unset( $this->arrOptions['order'] );
                    }
                    break;

                case 'column':
                    if ( is_array( $varValue ) && !empty( $varValue ) ) {
                        $this->arrOptions['column'] = $varValue;
                    }
                    break;

                case 'value':
                    if ( is_array( $varValue ) && !empty( $varValue ) ) {
                        $this->arrOptions['value'] = $varValue;
                    }
                    break;

                case 'groupBy':
                    $this->arrOptions['groupBy'] = $varValue;
                    break;

                case 'groupByHl':
                    $this->arrOptions['groupByHl'] = $varValue;
                    break;

                case 'template':
                    $this->arrOptions['template'] = $varValue;
                    break;

                case 'language':
                    $this->arrOptions['language'] = $varValue;
                    break;
            }
        }

        $this->paginate();
        parent::__construct();
    }

    protected function paginate() {

        if (!$this->arrOptions['pagination'] && !\Input::post('reload')) {
            return null;
        }

        $arrOptions = $this->getModelOptions();
        $numTotal = 0;
        $arrOptions['limit'] = 0;
        $arrOptions['offset'] = 0;

        $objModel = new ModelWizard($this->strTable);
        $objModel = $objModel->getModel();
        $objTotal = $objModel->findAll($arrOptions);

        if ($objTotal !== null) {
            $numTotal = $objTotal->count();
            \Cache::set('limit_' . $this->arrOptions['id'], $numTotal);
        }

        if (\Input::post('reload')) {
            $intOffset = (int) \Input::post('reload') + 1;
            $intLimit = $this->arrOptions['limit'] * $intOffset;
            if ($intLimit > $numTotal) {
                $intLimit = $numTotal;
                \Cache::set('max_' . $this->arrOptions['id'], true);
            }
            $this->arrOptions['offset'] = 0;
            $this->arrOptions['limit'] = $intLimit;
            return null;
        }

        if (!$numTotal) {
            return null;
        }

        $numOffset = $this->arrOptions['offset'];

        if ( $this->arrOptions['offset'] ) {
            $numTotal -= $numOffset;
        }

        $numOffset = $this->getPageNumber();

        if ($this->arrOptions['limit'] > 0 && $this->arrOptions['offset']) {
            $numOffset += round( $this->arrOptions['offset'] / $this->arrOptions['limit'] );
        }

        $this->arrOptions['offset'] = ($numOffset - 1) * $this->arrOptions['limit'];
        $this->arrOptions['total'] = $numTotal;
    }

    protected function initializeDataContainer() {

        $objApplication = new Application();
        $objApplication->initializeDataContainerArrayByTable($this->strTable);

        if (!isset($GLOBALS['TL_DCA'][ $this->strTable ])) {

            \Controller::loadDataContainer($this->strTable);
        }
    }

    protected function getModelOptions() {

        $arrReturn = [];
        $arrOptions = ['limit', 'offset', 'pagination', 'order', 'column', 'value', 'distance', 'having', 'language'];

        foreach ($arrOptions as $strOption) {
            if (isset($this->arrOptions[ $strOption ])) {
                $arrReturn[$strOption] = $this->arrOptions[$strOption];
            }
        }

        if (isset($GLOBALS['TL_HOOKS']['getModelOptions']) && is_array($GLOBALS['TL_HOOKS']['getModelOptions'])) {
            foreach ($GLOBALS['TL_HOOKS']['getModelOptions'] as $arrCallback) {
                $this->import( $arrCallback[0] );
                $arrReturn = $this->{$arrCallback[0]}->{$arrCallback[1]}($arrReturn, $this->strTable, $this->arrOptions);
            }
        }

        if ($this->dcaExtractor->hasVisibility() == true && !$this->arrOptions['ignoreVisibility']) {
            if (!isset($arrReturn['column']) || !is_array($arrReturn['column'])) {
                $arrReturn['column'] = [];
            }
            $blnIsPreview = defined('BE_USER_LOGGED_IN') && BE_USER_LOGGED_IN === true;
            if (!$blnIsPreview) {
                $intTime = \Date::floorToMinute();
                $strTable = $GLOBALS['TL_DCA'][$this->strTable]['config']['_table'] ?: $this->strTable;
                $arrReturn['column'][] = "($strTable.start='' OR $strTable.start<='$intTime') AND ($strTable.stop='' OR $strTable.stop>'" . ($intTime + 60) . "') AND $strTable.published='1'";
            }
        }

        return $arrReturn;
    }

    protected function validOrigin($strValue, $strField) {
        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['inputType'] == 'multiColumnWizard' && is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['eval']['columnFields'])) {
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['eval']['columnFields'] as $arrField) {
                if ($arrField['inputType'] == 'fileTree') {
                    return false;
                }
            }
        }
        if ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['inputType'] == 'fileTree' && $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['eval']['multiple']) {
            return false;
        }
        return true;
    }

    protected function parseEntity($arrEntity) {

        $arrRow = [];
        $arrRow['origin'] = [];
        $arrRow['_table'] = $this->strTable;

        if ($this->arrOptions['masterPage']) {
            $arrRow['masterUrl'] = Toolkit::parseDetailLink($this->arrMasterPage, $arrEntity['alias']);
        }

        foreach ($arrEntity as $strField => $varValue) {
            $strParsedValue = $this->parseField($varValue, $strField, $arrEntity, $this->arrOptions['fastMode']);
            if ($strParsedValue !== $varValue) {
                if ($this->validOrigin($varValue, $strField)) {
                    if (\Validator::isBinaryUuid($varValue)) {
                        $varValue = \StringUtil::binToUuid($varValue);
                    }
                    $arrRow['origin'][$strField] = $varValue;
                }
            }
            $arrRow[$strField] = $strParsedValue;
        }

        $arrRow['roleResolver'] = function () use ($arrRow) {
            return \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance($this->strTable, $arrRow);
        };

        $arrRow['shareButtons'] = function () use ($arrRow) {
            return (new \Alnv\ContaoCatalogManagerBundle\Library\ShareButtons($arrRow))->getShareButtons();
        };

        $arrRow['iCalendarUrl'] = function () use ($arrRow) {
            return (new \Alnv\ContaoCatalogManagerBundle\Library\ICalendar($arrRow))->getICalendarUrl();
        };

        $arrRow['getRelated'] = function ($strField) use ($arrRow) {
            if (!isset($arrRow[$strField]) || empty($arrRow[$strField])) {
                return [];
            }
            if (!isset($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField])) {
                return [];
            }
            if (!is_array($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['relation']) || empty($GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['relation'])) {
                return [];
            }
            $arrColumns = [];
            $arrValues = [];
            foreach ($arrRow[$strField] as $varValue) {
                if (is_string($varValue)) {
                    $arrValues[] = $varValue;
                    continue;
                }
                if (is_array($varValue) && isset($varValue['value'])) {
                    $arrValues[] = $varValue['value'];
                    continue;
                }
                $varValue = array_values($varValue);
                foreach ($varValue as $strValue) {
                    if ($strValue == '' || $strValue == null) {
                        continue;
                    }
                    $arrValues[] = $strValue;
                }
            }
            $arrRelation = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField]['relation'];
            \Controller::loadDataContainer($arrRelation['table']);
            $strTable = isset($GLOBALS['TL_DCA'][$arrRelation['table']]['config']['_table']) ? $GLOBALS['TL_DCA'][$arrRelation['table']]['config']['_table'] : $arrRelation['table'];
            $strField = $strTable .'.'. $arrRelation['field'];
            foreach ($arrValues as $strValue) {
                if ($strValue == '' || $strValue == null) {
                    continue;
                }
                $arrColumns[] = 'FIND_IN_SET(?,'. $strField .')';
            }

            if (empty($arrValues)) {
                return [];
            }

            $objList = new Listing($arrRelation['table'], [
                'column' => [implode('OR ', $arrColumns)],
                'value' => $arrValues,
                'order' => 'FIELD('. $strField .', '. implode(',', $arrValues) .')' // @exp.
            ]);

            return $objList->parse();
        };

        $arrRow['getParent'] = function () use ($arrRow) {
            if (!isset($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']) || !$GLOBALS['TL_DCA'][$this->strTable]['config']['ptable']) {
                return [];
            }
            $objMaster = new Master($GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'], [
                'alias' => $arrRow['pid'],
                'ignoreVisibility' => true,
                'limit' => 1
            ]);

            return $objMaster->parse()[0];
        };

        $arrRow['getContentElements'] = function () use ($arrRow) {
            $strReturn = '';
            $objContent = \ContentModel::findPublishedByPidAndTable($arrRow['id'], $this->strTable);
            if ( $objContent === null ) {
                return $strReturn;
            }
            while ($objContent->next()) {
                $strReturn .= $this->getContentElement($objContent->current());
            }
            return $strReturn;
        };

        if (isset($GLOBALS['TL_HOOKS']['parseEntity']) && is_array($GLOBALS['TL_HOOKS']['parseEntity'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseEntity'] as $arrCallback) {
                if (is_array($arrCallback)) {
                    $this->import($arrCallback[0]);
                    $this->{$arrCallback[0]}->{$arrCallback[1]}($arrRow, $this->strTable, $this->arrOptions, $this);
                }
                elseif (\is_callable($arrCallback)) {
                    $arrCallback($arrRow, $this->strTable, $this->arrOptions, $this);
                }
            }
        }

        if ($this->arrOptions['template']) {
            $objTemplate = new \FrontendTemplate($this->arrOptions['template']);
            $objTemplate->setData($arrRow);
            $arrRow['template'] =  $objTemplate->parse();
        }

        if ($this->arrOptions['groupBy']) {
            $strGroup = $arrEntity[ $this->arrOptions['groupBy'] ];
            if (!isset( $this->arrEntities[$strGroup])) {
                $this->arrEntities[$strGroup] = [
                    'headline' => \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::parse($arrRow[$this->arrOptions['groupBy']]),
                    'hl' => $this->arrOptions['groupByHl'],
                    'entities' => []
                ];
            }
            $this->arrEntities[$strGroup]['entities'][] = $arrRow;
        } else {
            $this->arrEntities[] = $arrRow;
        }

        return $arrEntity;
    }

    protected function parseField( $varValue, $strField, $arrValues, $blnFastMode ) {

        return Toolkit::parseCatalogValue($varValue, \Widget::getAttributesFromDca($this->dcaExtractor->getField($strField), $strField, $varValue, $strField, $this->strTable), $arrValues, false, $blnFastMode, $this->arrOptions['isForm']);
    }

    protected function getPageNumber() {

        return (int) \Input::get('page_e' . $this->arrOptions['id']);
    }

    public function getPagination() {

        if ( !$this->arrOptions['pagination'] ) {
            return '';
        }

        $objPagination = new \Pagination( $this->arrOptions['total'], $this->arrOptions['limit'], \Config::get('maxPaginationLinks'), 'page_e' . $this->arrOptions['id'] );

        return $objPagination->generate("\n  ");
    }

    public function getAddUrl() {

        return Toolkit::parseDetailLink($this->arrFormPage, '');
    }

    public function getEntities() {

        if ( isset($GLOBALS['TL_HOOKS']['parseViewEntities']) && is_array($GLOBALS['TL_HOOKS']['parseViewEntities'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseViewEntities'] as $arrCallback) {
                $this->import( $arrCallback[0] );
                $this->{$arrCallback[0]}->{$arrCallback[1]}($this->arrEntities, $this);
            }
        }

        return $this->arrEntities;
    }

    public function getTable() {

        return $this->strTable;
    }

    public function getModuleId() {

        return $this->arrOptions['id'];
    }

    abstract public function parse();
}