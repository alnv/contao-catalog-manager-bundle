<?php

namespace Alnv\ContaoCatalogManagerBundle\Views;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Application;
use Alnv\ContaoCatalogManagerBundle\Library\DcaExtractor;
use Alnv\ContaoCatalogManagerBundle\Library\ICalendar;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Library\ShareButtons;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Alnv\ContaoCatalogManagerBundle\Library\PlaceholderDataContainer;
use Contao\Database;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\Widget;

abstract class View extends Controller
{
    public array $arrFormPage = [];
    public array $arrMasterPage = [];
    protected ?string $strTable = null;
    protected array $arrOptions = [];
    protected array $arrEntities = [];
    protected ?DcaExtractor $dcaExtractor = null;

    public function __construct(string $strTable, array $arrOptions = [])
    {
        $this->strTable = $strTable;
        $this->initializeDataContainer();
        $this->dcaExtractor = new DcaExtractor($strTable);

        $arrIntOptions = ['id', 'limit', 'offset'];
        $arrBoolOptions = ['isForm', 'useAbsoluteUrl', 'fastMode', 'stringMode'];
        $arrArrayOptions = ['column', 'value', 'ignoreFieldsFromParsing'];

        foreach ($arrOptions as $strName => $varValue) {
            if (in_array($strName, $arrIntOptions, true)) {
                $this->arrOptions[$strName] = (int)$varValue;
                continue;
            }
            if (in_array($strName, $arrBoolOptions, true)) {
                $this->arrOptions[$strName] = (bool)$varValue;
                continue;
            }
            if (in_array($strName, $arrArrayOptions, true)) {
                if (is_array($varValue) && !empty($varValue)) {
                    $this->arrOptions[$strName] = $varValue;
                }
                continue;
            }

            switch ($strName) {
                case 'masterPage':
                case 'formPage':
                    if ($objPage = PageModel::findByPk($varValue)) {
                        $strProp = 'arr' . ucfirst($strName);
                        $this->{$strProp} = $objPage->row();
                        $this->arrOptions[$strName] = true;
                    }
                    break;

                case 'order':
                    $strOrder = $varValue ?: $this->dcaExtractor->getOrderBy();
                    if ($strOrder) {
                        $this->arrOptions['order'] = $strOrder;
                    }
                    break;

                case 'alias':
                case 'masterUrl':
                case 'pagination':
                case 'distance':
                case 'having':
                case 'ignoreVisibility':
                case 'groupBy':
                case 'groupByHl':
                case 'template':
                case 'language':
                    $this->arrOptions[$strName] = $varValue;
                    break;
            }
        }

        $this->paginate();

        if (isset($GLOBALS['TL_HOOKS']['initializeView']) && is_array($GLOBALS['TL_HOOKS']['initializeView'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeView'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($this->strTable, $this->arrOptions, $this);
            }
        }

        parent::__construct();
    }

    protected function paginate(): void
    {
        if (!($this->arrOptions['pagination'] ?? false) && !Input::post('reload')) {
            return;
        }

        $arrOptions = $this->getModelOptions();
        $numTotal = 0;
        $arrOptions['limit'] = 0;
        $arrOptions['offset'] = 0;

        $this->arrOptions['offset'] ??= 0;
        $this->arrOptions['limit'] ??= 0;

        $objModel = (new ModelWizard($this->strTable))->getModel();
        $objTotal = $objModel->findAll($arrOptions);

        if ($objTotal !== null) {
            $numTotal = $objTotal->count();
            Cache::set('limit_' . $this->arrOptions['id'], $numTotal);
        }

        if (Input::post('reload')) {
            $intOffset = (int)Input::post('reload') + 1;
            $intLimit = $this->arrOptions['limit'] * $intOffset;
            if ($intLimit > $numTotal) {
                $intLimit = $numTotal;
                Cache::set('max_' . $this->arrOptions['id'], true);
            }
            $this->arrOptions['offset'] = 0;
            $this->arrOptions['limit'] = $intLimit;
            return;
        }

        if (!$numTotal) {
            return;
        }

        $numOffset = $this->arrOptions['offset'];
        if ($numOffset) {
            $numTotal -= $numOffset;
        }

        $numOffset = $this->getPageNumber();
        if ($this->arrOptions['limit'] > 0 && $this->arrOptions['offset']) {
            $numOffset += (int) round($this->arrOptions['offset'] / $this->arrOptions['limit']);
        }

        $this->arrOptions['offset'] = ($numOffset - 1) * $this->arrOptions['limit'];
        $this->arrOptions['total'] = $numTotal;
    }

    protected function initializeDataContainer(): void
    {
        (new Application())->initializeDataContainerArrayByTable($this->strTable);

        if (!isset($GLOBALS['TL_DCA'][$this->strTable])) {
            Controller::loadDataContainer($this->strTable);
        }
    }

    public function getOptions(): array
    {
        return $this->arrOptions;
    }

    public function getModelOptions(): array
    {
        $arrReturn = [];
        $arrOptions = ['limit', 'offset', 'pagination', 'order', 'column', 'value', 'distance', 'having', 'language'];

        foreach ($arrOptions as $strOption) {
            if (isset($this->arrOptions[$strOption])) {
                $arrReturn[$strOption] = $this->arrOptions[$strOption];
            }
        }

        if (isset($GLOBALS['TL_HOOKS']['getModelOptions']) && is_array($GLOBALS['TL_HOOKS']['getModelOptions'])) {
            foreach ($GLOBALS['TL_HOOKS']['getModelOptions'] as $arrCallback) {
                $this->import($arrCallback[0]);
                $arrReturn = $this->{$arrCallback[0]}->{$arrCallback[1]}($arrReturn, $this->strTable, $this->arrOptions);
            }
        }

        if ($this->dcaExtractor->hasVisibility() && !($this->arrOptions['ignoreVisibility'] ?? false)) {
            $tokenChecker = System::getContainer()->get('contao.security.token_checker');
            $blnIsPreview = $tokenChecker->isPreviewMode() && $tokenChecker->hasBackendUser();

            if (!$blnIsPreview) {
                $arrReturn['column'] ??= [];
                $intTime = Date::floorToMinute();
                $strTable = ($GLOBALS['TL_DCA'][$this->strTable]['config']['_table'] ?? '') ?: $this->strTable;
                $strTableEscaped = Database::quoteIdentifier($strTable);

                $arrReturn['column'][] = sprintf(
                    "(%1\$s.start='' OR %1\$s.start<='%2\$d') AND (%1\$s.stop='' OR %1\$s.stop>'%3\$d') AND %1\$s.published='1'",
                    $strTableEscaped,
                    $intTime,
                    $intTime + 60
                );
            }
        }

        return $arrReturn;
    }

    protected function validOrigin($strValue, $strField): bool
    {
        $fieldConfig = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField] ?? [];

        if (($fieldConfig['inputType'] ?? '') === 'rowWizard' && is_array($fieldConfig['fields'] ?? null)) {
            foreach ($fieldConfig['fields'] as $arrField) {
                if (($arrField['inputType'] ?? '') === 'fileTree') {
                    return false;
                }
            }
        }

        if (($fieldConfig['inputType'] ?? '') === 'fileTree' && ($fieldConfig['eval']['multiple'] ?? false)) {
            return false;
        }

        return true;
    }

    protected function parseEntity($arrEntity): array
    {
        $arrRow = [
            'origin' => [],
            '_table' => $this->strTable,
            'masterUrl' => $this->arrOptions['masterUrl'] ?? ''
        ];

        if ($this->arrOptions['masterPage'] ?? false) {
            $arrRow['masterUrl'] = Toolkit::parseDetailLink($this->arrMasterPage, $arrEntity['alias'] ?? '', $arrEntity, ($this->arrOptions['useAbsoluteUrl'] ?? false));
        }

        foreach ($arrEntity as $strField => $varValue) {
            $strParsedValue = $this->parseField($varValue, $strField, $arrEntity, [
                'fastMode' => $this->arrOptions['fastMode'] ?? false,
                'stringMode' => $this->arrOptions['stringMode'] ?? false,
                'ignoreFieldsFromParsing' => $this->arrOptions['ignoreFieldsFromParsing'] ?? []
            ]);
            if ($strParsedValue !== $varValue && $this->validOrigin($varValue, $strField)) {
                if (Validator::isBinaryUuid($varValue)) {
                    $varValue = StringUtil::binToUuid($varValue);
                }
                $arrRow['origin'][$strField] = $varValue;
            }
            $arrRow[$strField] = $strParsedValue;
        }

        $arrRow['roleResolver'] = fn() => RoleResolver::getInstance($this->strTable, $arrRow);
        $arrRow['shareButtons'] = fn() => (new ShareButtons($arrRow))->getShareButtons();
        $arrRow['iCalendarUrl'] = fn() => (new ICalendar($arrRow))->getICalendarUrl();

        $arrRow['getRelated'] = function ($strField) use ($arrRow) {
            $fieldDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$strField] ?? null;
            if (empty($arrRow[$strField]) || !$fieldDca || empty($fieldDca['relation'] ?? [])) {
                return [];
            }

            $arrValues = [];
            $arrColumns = [];
            $varValues = $arrRow[$strField];

            if (isset($arrRow['origin'][$strField])) {
                $varOriginValues = StringUtil::deserialize($arrRow['origin'][$strField]);
                if (is_string($varOriginValues)) {
                    $varOriginValues = explode(',', $varOriginValues);
                }
                if (is_array($varOriginValues)) {
                    $varValues = $varOriginValues;
                }
            }

            foreach ((array)$varValues as $varValue) {
                if (is_string($varValue) || is_numeric($varValue)) {
                    $arrValues[] = $varValue;
                    continue;
                }
                if (is_array($varValue) && isset($varValue['value'])) {
                    $arrValues[] = $varValue['value'];
                    continue;
                }
                foreach (array_values((array)$varValue) as $strValue) {
                    if ($strValue !== '' && $strValue !== null) {
                        $arrValues[] = $strValue;
                    }
                }
            }

            if (empty($arrValues)) {
                return [];
            }

            $arrRelation = $fieldDca['relation'];
            Controller::loadDataContainer($arrRelation['table']);

            $strRelTable = $GLOBALS['TL_DCA'][$arrRelation['table']]['config']['_table'] ?? $arrRelation['table'];
            $strRelFieldEscaped = Database::quoteIdentifier($strRelTable) . '.' . Database::quoteIdentifier($arrRelation['field']);

            foreach ($arrValues as $strValue) {
                if ($strValue !== '' && $strValue !== null) {
                    $arrColumns[] = 'FIND_IN_SET(?,' . $strRelFieldEscaped . ')';
                }
            }

            $objList = new Listing($arrRelation['table'], [
                'column' => [implode(' OR ', $arrColumns)],
                'value' => $arrValues,
                'order' => 'FIELD(' . $strRelFieldEscaped . ', ' . implode(',', array_map('intval', $arrValues)) . ')'
            ]);

            return $objList->parse();
        };

        $arrRow['getParent'] = function () use ($arrRow) {
            $pTable = $GLOBALS['TL_DCA'][$this->strTable]['config']['ptable'] ?? null;
            if (!$pTable) {
                return [];
            }
            $objMaster = new Master($pTable, [
                'alias' => $arrRow['pid'] ?? 0,
                'ignoreVisibility' => true,
                'limit' => 1
            ]);
            return $objMaster->parse()[0] ?? [];
        };

        $arrRow['getContentElements'] = function () use ($arrRow) {
            $strReturn = '';
            $objContent = ContentModel::findPublishedByPidAndTable($arrRow['id'], $this->strTable);
            if ($objContent === null) {
                return $strReturn;
            }
            while ($objContent->next()) {
                $strReturn .= $this->getContentElement($objContent->current());
            }
            return $strReturn;
        };

        $arrRow['parseImage'] = fn($varValue) => Toolkit::parseImage($varValue);
        $arrRow['parseArray'] = fn($varValue, $strDelimiter = ', ', $strField = 'label') => Toolkit::parse($varValue, $strDelimiter, $strField);

        if (isset($GLOBALS['TL_HOOKS']['parseEntity']) && is_array($GLOBALS['TL_HOOKS']['parseEntity'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseEntity'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrRow, $this->strTable, $this->arrOptions, $this);
            }
        }

        if ($this->arrOptions['template'] ?? false) {
            $objTemplate = new FrontendTemplate($this->arrOptions['template']);
            $objTemplate->setData($arrRow);
            $arrRow['template'] = $objTemplate->parse();
        }

        if ($this->arrOptions['groupBy'] ?? false) {
            $strGroup = $arrEntity[$this->arrOptions['groupBy']];
            $this->arrEntities[$strGroup] ??= [
                'headline' => Toolkit::parse($arrRow[$this->arrOptions['groupBy']]),
                'hl' => $this->arrOptions['groupByHl'],
                'entities' => []
            ];
            $this->arrEntities[$strGroup]['entities'][] = $arrRow;
        } else {
            $this->arrEntities[] = $arrRow;
        }

        return $arrEntity;
    }

    protected function parseField($varValue, $strField, $arrValues, $arrOptions = [])
    {
        $blnFastMode = $arrOptions['fastMode'] ?? false;
        $blnStringMode = $arrOptions['stringMode'] ?? false;
        $arrIgnoreFieldsFromParsing = $arrOptions['ignoreFieldsFromParsing'] ?? [];

        if (isset($GLOBALS['TL_HOOKS']['parseFieldValue']) && is_array($GLOBALS['TL_HOOKS']['parseFieldValue'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseFieldValue'] as $arrCallback) {
                $strCallback = System::importStatic($arrCallback[0])->{$arrCallback[1]}($varValue, $strField, $arrValues, $this->strTable, $blnFastMode, $this);
                if ($strCallback !== null) {
                    return $strCallback;
                }
            }
        }

        if (!empty($arrIgnoreFieldsFromParsing) && in_array($strField, $arrIgnoreFieldsFromParsing, true)) {
            return $varValue;
        }

        $strHash = md5($this->strTable . '_' . $strField . '_' . (is_array($varValue) ? serialize($varValue) : $varValue));
        if (Cache::has($strHash)) {
            $arrAttribute = Cache::get($strHash);
        } else {
            $objDataContainer = new PlaceholderDataContainer($this->strTable);
            $arrAttribute = Widget::getAttributesFromDca($this->dcaExtractor->getField($strField), $strField, $varValue, $strField, $this->strTable, $objDataContainer);
            Cache::set($strHash, $arrAttribute);
        }

        return Toolkit::parseCatalogValue($varValue, $arrAttribute, $arrValues, $blnStringMode, $blnFastMode, ($this->arrOptions['isForm'] ?? false));
    }

    protected function getPageNumber(): int
    {
        return (int)Input::get('page_e' . ($this->arrOptions['id'] ?? 0));
    }

    public function getPagination(): string
    {
        if (!($this->arrOptions['pagination'] ?? '')) {
            return '';
        }

        $objPagination = new Pagination(($this->arrOptions['total'] ?? 0), ($this->arrOptions['limit'] ?? 0), Config::get('maxPaginationLinks'), 'page_e' . ($this->arrOptions['id'] ?? 0));
        return $objPagination->generate("\n  ");
    }

    public function getAddUrl(): string
    {
        return Toolkit::parseDetailLink($this->arrFormPage, '');
    }

    public function getEntities(): array
    {
        if (isset($GLOBALS['TL_HOOKS']['parseViewEntities']) && is_array($GLOBALS['TL_HOOKS']['parseViewEntities'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseViewEntities'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($this->arrEntities, $this);
            }
        }

        return $this->arrEntities;
    }

    public function getTable(): string
    {
        return $this->strTable;
    }

    public function getModuleId(): string
    {
        return (string)($this->arrOptions['id'] ?? '0');
    }

    abstract public function parse();
}