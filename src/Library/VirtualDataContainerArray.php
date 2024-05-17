<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class VirtualDataContainerArray
{

    protected array $arrCatalog = [];

    protected array $arrFields = [];

    public function __construct($strModule)
    {

        $objCatalog = new Catalog($strModule);
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();
        $this->generateEmptyDataContainer();
    }

    protected function setConfig(): void
    {

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['_table'] = $this->arrCatalog['table'];
        if ($this->arrCatalog['ptable']) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['ptable'] = $this->arrCatalog['ptable'];
        }

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['ctable'] = $this->arrCatalog['ctable'];
        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['dataContainer'] = $this->getDataContainerNamespace($this->arrCatalog['dataContainer']);

        if ($this->arrCatalog['enableGeocoding']) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['onsubmit_callback'][] = function (DataContainer $objDataContainer) {
                if ($objDataContainer->activeRecord) {
                    Toolkit::saveGeoCoordinates($this->arrCatalog['table'], Toolkit::getActiveRecordAsArrayFromDc($objDataContainer));
                }
            };
        }

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['onload_callback'][] = function ($objDataContainer = null) {

            if (!$objDataContainer) {
                return;
            }

            if ($objDataContainer->id) {
                $objActiveRecord = Database::getInstance()->prepare('SELECT * FROM ' . $objDataContainer->table . ' WHERE id=?')->limit(1)->execute($objDataContainer->id);

                if (!$objActiveRecord->numRows) {
                    return;
                }

                Cache::set('activeRecord', $objActiveRecord->row());
            }
        };

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['enableVersioning'] = true;
        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['hasVisibilityFields'] = (bool)$this->arrCatalog['enableVisibility'];
    }

    protected function getDataContainerNamespace($strDataContainer)
    {
        return $GLOBALS['CM_DATA_CONTAINERS_NAMESPACE'][$strDataContainer] ?? $strDataContainer;
    }

    protected function setList(): void
    {

        $arrList = [
            'labels' => [
                'fields' => ['id']
            ],
            'sorting' => [
                'mode' => 0
            ]
        ];

        if ($this->arrCatalog['enablePanel']) {
            $arrList['sorting']['panelLayout'] = 'filter,search,sort;limit';
        }

        if ($this->arrCatalog['showColumns']) {
            $arrList['labels']['showColumns'] = true;
        }

        if (!empty($this->arrCatalog['columns'])) {
            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
        }

        if ($this->arrCatalog['sortingType']) {
            if ($this->arrCatalog['sortingType'] == 'fixed') {
                $arrList['sorting']['mode'] = 1;
                $arrList['sorting']['flag'] = (int)$this->arrCatalog['flag'];
                $arrList['sorting']['fields'] = [$this->arrCatalog['flagField']];
                if (empty($arrList['labels']['fields'])) {
                    $arrList['labels']['fields'] = [$this->arrCatalog['flagField']];
                }
            }
            if ($this->arrCatalog['sortingType'] == 'switchable') {
                $arrSortingFields = [];
                $arrList['sorting']['mode'] = 2;
                $arrList['sorting']['fields'] = [];
                foreach ($this->arrCatalog['order'] as $arrOrder) {
                    if (isset($arrOrder['field']) && $arrOrder['field']) {
                        $arrList['sorting']['fields'][] = $arrOrder['field'] . ($arrOrder['order'] ? ' ' . $arrOrder['order'] : '');
                        $arrSortingFields[] = $arrOrder['field'];
                    }
                }
                if (empty($arrList['labels']['fields'])) {
                    $arrList['labels']['fields'] = $arrSortingFields;
                }
            }

            if (in_array($this->arrCatalog['sortingType'], ['fixed', 'switchable']) && !$this->arrCatalog['showColumns']) {
                $arrList['labels']['group_callback'] = function ($strGroupValue, $strMode, $strField, $arrRecord, DataContainer $dc) {
                    try {
                        $varReturn = Toolkit::parseCatalogValue($strGroupValue, Widget::getAttributesFromDca($this->arrFields[$strField], $strField, $strGroupValue, $strField, $dc->table), $arrRecord, true);
                    } catch (\Exception $objError) {
                        $varReturn = '';
                    }
                    return $varReturn ?: '';
                };
            }
        }

        if (count($arrList['labels']['fields']) > 0) {
            $arrList['labels']['label_callback'] = function ($arrRow, $strLabel, DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false) use ($arrList) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };
        }

        if ($this->arrCatalog['mode'] == 'parent') {
            $arrList['sorting']['mode'] = 4;
            $arrList['sorting']['headerFields'] = empty($this->arrCatalog['headerFields']) ? ['id'] : $this->arrCatalog['headerFields'];
            $arrList['sorting']['child_record_callback'] = function ($arrRow) use ($arrList) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };

            $arrList['labels']['showColumns'] = false;
        }

        if ($this->arrCatalog['mode'] == 'tree') {
            $arrList['sorting']['mode'] = 5;
            $arrList['sorting']['fields'] = ['sorting'];
            $arrList['sorting']['icon'] = 'articles.svg'; // @todo icon
            $arrList['labels']['fields'] = $this->arrCatalog['columns'];
            $arrList['labels']['label_callback'] = function ($arrRow, $strLabel, DataContainer $dc = null, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false) use ($arrList) {
                return Toolkit::renderTreeRow($arrRow, $strLabel, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };
            $arrList['sorting']['fields'] = [];
            $arrList['labels']['showColumns'] = false;
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'], 1, [
                'cut' => [
                    'icon' => 'cut.svg',
                    'href' => 'act=paste&amp;mode=cut',
                    'attributes' => 'onclick="Backend.getScrollOffset()"'
                ]
            ]);
        }

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['label'] = $arrList['labels'];
        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['sorting'] = $arrList['sorting'];

        if ($this->arrCatalog['enableCopy']) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'], 1, [
                'copy' => [
                    'href' => 'act=copy',
                    'icon' => 'copy.svg'
                ]
            ]);
        }

        if ($this->arrCatalog['enableVisibility']) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'], count($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations']) - 1, [
                'toggle' => [
                    'href' => 'act=toggle&amp;field=published',
                    'icon' => 'visible.svg',
                    'showInHeader' => true
                ]
            ]);
        }
    }

    protected function setFields(): void
    {
        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['fields'] = $this->arrFields;
    }

    protected function setPalettes()
    {

        $objPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=? AND pid=?'],
            'value' => ['palette', '1', $this->arrCatalog['id']],
            'sorting' => 'sorting ASC'
        ]);

        if (!isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['__selector__'] = [];
        }

        if (!$objPalettes) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['default'] = implode(',', $this->getDefaultPalettes());
            return null;
        }

        $arrPalettes = [
            'default' => []
        ];

        while ($objPalettes->next()) {

            $arrLegends = [];
            $strLegend = '';
            $strName = StringUtil::generateAlias(strtolower($objPalettes->name));
            $arrFields = StringUtil::deserialize($objPalettes->fields, true);
            $arrFieldsets = StringUtil::deserialize($objPalettes->fieldsets, true);

            foreach ($arrFields as $arrField) {

                if ($arrField['field'] == '__FIELDSET__') {
                    $arrFieldset = current($arrFieldsets);
                    $strLegend = StringUtil::generateAlias($arrFieldset['label']) . '_legend';
                    $GLOBALS['TL_LANG'][$this->arrCatalog['table']][$strLegend] = Translation::getInstance()->translate(($this->arrCatalog['table'] ? $this->arrCatalog['table'] . '.' : '') . 'fieldset.' . $strLegend, $arrFieldset['label']);
                    $strLegend .= ($arrFieldset['hide'] ? ':hide' : '');
                    next($arrFieldsets);
                    continue;
                }

                if (!isset($arrLegends[$strLegend])) {
                    $arrLegends[$strLegend] = [];
                }

                $strField = $arrField['field'];
                if (is_numeric($arrField['field'])) {
                    $objField = CatalogFieldModel::findByPk($arrField['field']);
                    if (!$objField) {
                        continue;
                    }
                    $strField = $objField->fieldname;
                }

                $arrLegends[$strLegend][] = $strField;
            }

            $strLegendFields = '';
            foreach ($arrLegends as $strLegend => $arrFields) {
                if (!$strLegend) {
                    $strLegendFields .= implode(',', $arrFields) . ';';
                } else {
                    $strLegendFields .= '{' . $strLegend . '},' . implode(',', $arrFields) . ';';
                }
            }

            if (empty($arrPalettes['default'])) {
                $strName = 'default';
            }

            if ($objPalettes->selector_type) {
                if (!in_array('type', $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['__selector__'])) {
                    $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['__selector__'][] = 'type';
                }

                $strName = $objPalettes->selector_type;
            }

            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes'][$strName] = $strLegendFields;
        }
    }

    protected function addSubmitOnChange($strField): void
    {

        if (isset($this->arrFields[$strField]) && isset($this->arrFields[$strField]['eval'])) {
            $this->arrFields[$strField]['eval']['submitOnChange'] = true;
        }
    }

    protected function getDefaultPalettes(): array
    {

        $arrReturn = [];
        foreach ($this->arrFields as $strFieldname => $arrField) {

            $strType = $this->arrFields['type'] ?? '';

            if ($strType == 'empty') {
                continue;
            }

            if (!$this->arrCatalog['enableVisibility'] && in_array($strFieldname, ['published', 'start', 'stop'])) {
                continue;
            }

            $arrReturn[] = $strFieldname;
        }

        return $arrReturn;
    }

    protected function setSubPalettes()
    {

        $objSubPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=?'],
            'value' => ['subpalette', '1'],
            'sorting' => 'sorting ASC'
        ]);

        if (!$objSubPalettes) {
            return null;
        }

        if (!isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['subpalettes'])) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['subpalettes'] = [];
        }

        while ($objSubPalettes->next()) {

            $strFieldname = $objSubPalettes->selector;
            if (is_numeric($objSubPalettes->selector)) {
                $objField = CatalogFieldModel::findByPk($objSubPalettes->selector);
                if (!$objField) {
                    continue;
                }
                $strFieldname = $objField->fieldname;
            }

            $arrFields = $this->getFieldsOnly(StringUtil::deserialize($objSubPalettes->fields, true));
            $strPalette = $strFieldname;
            $this->addSubmitOnChange($strFieldname);
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['palettes']['__selector__'][] = $strFieldname;

            if ($objSubPalettes->selector_option) {
                $strPalette .= '_' . $objSubPalettes->selector_option;
            }

            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['subpalettes'][$strPalette] = empty($arrFields) ? '' : implode(',', $arrFields);
        }
    }

    protected function getFieldsOnly($arrFields): array
    {

        $arrReturn = [];

        foreach ($arrFields as $arrField) {

            $strField = $arrField['field'];

            if (is_numeric($strField)) {
                $objField = CatalogFieldModel::findByPk($arrField['field']);
                if (!$objField) {
                    continue;
                }
                $strField = $objField->fieldname;
            }

            $arrReturn[] = $strField;
        }

        return $arrReturn;
    }

    protected function setLabels(): void
    {

        foreach ($this->arrFields as $strFieldname => $arrField) {

            if (isset($GLOBALS['TL_LANG'][$this->arrCatalog['table']][$strFieldname])) {
                continue;
            }

            $strName = isset($arrField['name']) && $arrField['name'] ? $arrField['name'] : '';
            $GLOBALS['TL_LANG'][$this->arrCatalog['table']][$strFieldname] = [
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.' . $strFieldname, $strName),
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description' . $strFieldname, $strName)
            ];
        }
    }

    protected function generateEmptyDataContainer()
    {

        if (!isset($this->arrCatalog['table'])) {
            return null;
        }

        if (!isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']])) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']] = [];
        }

        $GLOBALS['TL_DCA'][$this->arrCatalog['table']] = [
            'config' => [
                'onsubmit_callback' => [
                    function (DataContainer $objDataContainer) {
                        if ($objDataContainer->activeRecord) {
                            Toolkit::saveAlias(Toolkit::getActiveRecordAsArrayFromDc($objDataContainer), $this->arrFields, $this->arrCatalog);
                        }
                    }
                ],
                'sql' => [
                    'keys' => [
                        'id' => 'primary'
                    ]
                ]
            ],
            'list' => [
                'label' => [],
                'sorting' => [],
                'operations' => [
                    'edit' => [
                        'href' => 'act=edit',
                        'icon' => 'header.svg'
                    ],
                    'delete' => [
                        'href' => 'act=delete',
                        'icon' => 'delete.svg',
                        'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
                    ],
                    'show' => [
                        'href' => 'act=show',
                        'icon' => 'show.svg'
                    ]
                ],
                'global_operations' => [
                    'all' => [
                        'href' => 'act=select',
                        'class' => 'header_edit_all',
                        'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
                    ]
                ]
            ],
            'palettes' => ['__selector__' => [], 'default' => ''],
            'subpalettes' => [],
            'fields' => []
        ];
    }

    public function getRelatedTables()
    {

        if (!isset($this->arrCatalog['related'])) {
            return [];
        }

        return $this->arrCatalog['related'];
    }

    protected function setOperations()
    {

        if (empty($this->arrCatalog['ctable']) || !is_array($this->arrCatalog['ctable'])) {
            return null;
        }

        foreach ($this->arrCatalog['ctable'] as $strTable) {
            $strTitle = '';
            $strDescription = '';
            $objCatalog = CatalogModel::findByTableOrModule($strTable);
            if ($objCatalog !== null) {
                $strTitle = $objCatalog->name;
                $strDescription = $objCatalog->description;
            }
            $arrOperation = [];
            $arrOperation['child_' . $strTable] = [
                'label' => [
                    Translation::getInstance()->translate('child_' . $strTable . '.title', $strTitle),
                    Translation::getInstance()->translate('child_' . $strTable . '.description', ($strDescription ?: $strTitle)),
                ],
                'href' => 'table=' . $strTable . '&sourceTable=' . $this->arrCatalog['table'],
                'icon' => 'edit.svg'
            ];
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'], 1, $arrOperation);
        }
    }

    public function generate()
    {

        if (empty($this->arrCatalog)) {
            return null;
        }

        $this->setConfig();
        $this->setList();
        $this->setOperations();
        $this->setPalettes();
        $this->setSubPalettes();
        $this->setFields();
        $this->setLabels();

        if (isset($GLOBALS['TL_HOOKS']['loadVirtualDataContainer']) && is_array($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($this->arrCatalog['table'], $this);
            }
        }
    }
}