<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class VirtualDataContainerArray
{
    protected array $arrCatalog = [];
    protected array $arrFields = [];

    protected static array $arrFieldIdToNameMap = [];

    public function __construct($strModule)
    {
        System::loadLanguageFile('default');

        $objCatalog = new Catalog($strModule);
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();

        foreach ($this->arrFields as $strFieldname => $arrField) {
            if (isset($arrField['id'])) {
                self::$arrFieldIdToNameMap[(int)$arrField['id']] = $strFieldname;
            }
        }

        $this->generateEmptyDataContainer();
    }

    protected function setConfig(): void
    {
        $strTable = $this->arrCatalog['table'];

        $GLOBALS['TL_DCA'][$strTable]['config']['backendSearchIgnore'] = true;
        $GLOBALS['TL_DCA'][$strTable]['config']['_table'] = $strTable;
        $GLOBALS['TL_DCA'][$strTable]['config']['ctable'] = Toolkit::extendField(($GLOBALS['TL_DCA'][$strTable]['config']['ctable'] ?? []), ($this->arrCatalog['ctable'] ?? []));
        $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] = $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] ?? $this->getDataContainerNamespace($this->arrCatalog['dataContainer']);

        if ($this->arrCatalog['ptable']) {
            $GLOBALS['TL_DCA'][$strTable]['config']['ptable'] = $this->arrCatalog['ptable'];
        }

        if ($this->arrCatalog['enableGeocoding']) {
            $GLOBALS['TL_DCA'][$strTable]['config']['onsubmit_callback'][] = function (DataContainer $objDataContainer) use ($strTable) {
                if ($objDataContainer->activeRecord) {
                    Toolkit::saveGeoCoordinates($strTable, Toolkit::getActiveRecordAsArrayFromDc($objDataContainer));
                }
            };
        }

        $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = function ($objDataContainer = null) {
            if (!$objDataContainer || !$objDataContainer->id) {
                return;
            }

            $objActiveRecord = Database::getInstance()->prepare('SELECT * FROM ' . $objDataContainer->table . ' WHERE id=?')->limit(1)->execute($objDataContainer->id);

            if ($objActiveRecord && $objActiveRecord->numRows) {
                Cache::set('activeRecord', $objActiveRecord->row());
            }
        };

        $GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] = $GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] ?? true;
        $GLOBALS['TL_DCA'][$strTable]['config']['hasVisibilityFields'] = (bool)$this->arrCatalog['enableVisibility'];
    }

    protected function getDataContainerNamespace($strDataContainer)
    {
        return $GLOBALS['CM_DATA_CONTAINERS_NAMESPACE'][$strDataContainer] ?? $strDataContainer;
    }

    protected function setList(): void
    {
        $strTable = $this->arrCatalog['table'];
        $arrList = [
            'labels' => [
                'fields' => ['id']
            ],
            'sorting' => [
                'mode' => 0
            ]
        ];

        $blnUseCut = false;

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
            if ($this->arrCatalog['sortingType'] === 'fixed') {
                $arrList['sorting']['mode'] = DataContainer::MODE_SORTED;
                $arrList['sorting']['flag'] = (int)$this->arrCatalog['flag'];
                $arrList['sorting']['fields'] = [$this->arrCatalog['flagField']];

                if (empty($arrList['labels']['fields'])) {
                    $arrList['labels']['fields'] = [$this->arrCatalog['flagField']];
                }

                if ($this->arrCatalog['flagField'] === 'sorting' && $this->arrCatalog['mode'] !== 'parent') {
                    $arrList['sorting']['mode'] = DataContainer::MODE_TREE;
                    $arrList['sorting']['rootPaste'] = true;
                    $arrList['sorting']['showRootTrails'] = true;
                    $arrList['sorting']['fields'] = ['sorting'];

                    $arrList['sorting']['paste_button_callback'] = function (DataContainer $dc, $row, $table, $cr, $arrClipboard = null) {
                        return ($arrClipboard['mode'] === 'cut' && ($arrClipboard['id'] == $row['id'] || $cr)) ? Image::getHtml('pasteafter_.svg') . ' ' : '<a href="' . Backend::addToUrl('act=' . $arrClipboard['mode'] . '&mode=1&pid=' . $row['id'] . '&id=' . $arrClipboard['id']) . '" title="' . StringUtil::specialchars(\sprintf($GLOBALS['TL_LANG'][$dc->table]['pasteafter'][1], $row['id'])) . '" onclick="Backend.getScrollOffset();">' . Image::getHtml('pasteafter.svg', \sprintf($GLOBALS['TL_LANG'][$dc->table]['pasteafter'][1], $row['id'])) . '</a> ';
                    };

                    $blnUseCut = true;
                    $this->arrCatalog['showColumns'] = '';
                    unset($arrList['sorting']['flag']);
                }
            }

            if ($this->arrCatalog['sortingType'] === 'switchable') {
                $arrSortingFields = [];
                $arrList['sorting']['mode'] = DataContainer::MODE_SORTABLE;
                $arrList['sorting']['fields'] = [];
                foreach ($this->arrCatalog['order'] as $arrOrder) {
                    if (!empty($arrOrder['field'])) {
                        $arrList['sorting']['fields'][] = $arrOrder['field'] . ($arrOrder['order'] ? ' ' . $arrOrder['order'] : '');
                        $arrSortingFields[] = $arrOrder['field'];
                    }
                }
                if (empty($arrList['labels']['fields'])) {
                    $arrList['labels']['fields'] = $arrSortingFields;
                }
            }

            if (\in_array($this->arrCatalog['sortingType'], ['fixed', 'switchable'], true) && !$this->arrCatalog['showColumns']) {
                $arrList['labels']['group_callback'] = function ($strGroupValue, $strMode, $strField, $arrRecord, DataContainer $dc) {
                    try {
                        $varReturn = Toolkit::parseCatalogValue($strGroupValue, Widget::getAttributesFromDca($this->arrFields[$strField] ?? [], $strField, $strGroupValue, $strField, $dc->table), $arrRecord, true);
                    } catch (\Exception $objError) {
                        $varReturn = '';
                    }
                    return $varReturn ?: '';
                };
            }
        }

        if (\count($arrList['labels']['fields']) > 0) {
            $arrList['labels']['label_callback'] = function ($arrRow, $strLabel, DataContainer $dc, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false) use ($arrList) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };
        }

        if ($this->arrCatalog['mode'] === 'parent') {
            if (empty($arrList['sorting']['fields'])) {
                $arrList['sorting']['fields'] = empty($this->arrCatalog['columns']) ? ['id'] : $this->arrCatalog['columns'];
            }

            $arrList['sorting']['mode'] = DataContainer::MODE_PARENT;
            $arrList['sorting']['headerFields'] = empty($this->arrCatalog['headerFields']) ? ['id'] : $this->arrCatalog['headerFields'];
            $arrList['sorting']['child_record_callback'] = function ($arrRow) use ($arrList) {
                return Toolkit::renderRow($arrRow, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };

            $arrList['labels']['showColumns'] = false;
        }

        if ($this->arrCatalog['mode'] === 'tree') {
            $arrList['sorting']['mode'] = DataContainer::MODE_TREE;
            $arrList['sorting']['fields'] = ['sorting'];
            $arrList['sorting']['icon'] = 'articles.svg';
            $arrList['labels']['fields'] = empty($this->arrCatalog['columns']) ? ['id'] : $this->arrCatalog['columns'];
            $arrList['labels']['label_callback'] = function ($arrRow, $strLabel, DataContainer $dc, $strImageAttribute = '', $blnReturnImage = false, $blnProtected = false) use ($arrList) {
                return Toolkit::renderTreeRow($arrRow, $strLabel, $arrList['labels']['fields'], $this->arrCatalog, $this->arrFields);
            };
            $arrList['labels']['showColumns'] = false;
            $arrList['sorting']['rootPaste'] = true;
            $arrList['sorting']['showRootTrails'] = true;
            $blnUseCut = true;
        }

        // Performance-Fix: isset() statt in_array(..., array_keys(...))
        $operations = $GLOBALS['TL_DCA'][$strTable]['list']['operations'] ?? [];

        if ($blnUseCut && !isset($operations['cut'])) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$strTable]['list']['operations'], 1, [
                'cut' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['cut'] ?? '',
                    'icon' => 'cut.svg',
                    'href' => 'act=paste&amp;mode=cut',
                    'attributes' => 'onclick="Backend.getScrollOffset()"'
                ]
            ]);
        }

        $GLOBALS['TL_DCA'][$strTable]['list']['label'] = $arrList['labels'];
        $GLOBALS['TL_DCA'][$strTable]['list']['sorting'] = $arrList['sorting'];

        if ($this->arrCatalog['enableCopy'] && !isset($operations['copy'])) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$strTable]['list']['operations'], 1, [
                'copy' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['copy'] ?? '',
                    'href' => 'act=copy',
                    'icon' => 'copy.svg'
                ]
            ]);
        }

        if ($this->arrCatalog['enableVisibility'] && !isset($operations['toggle'])) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$strTable]['list']['operations'], \count($GLOBALS['TL_DCA'][$strTable]['list']['operations']) - 1, [
                'toggle' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['toggle'][0] ?? '',
                    'href' => 'act=toggle&amp;field=published',
                    'icon' => 'visible.svg',
                    'showInHeader' => true
                ]
            ]);
        }

        if (($this->arrCatalog['enablePreview'] ?? false) && !isset($operations['preview'])) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$strTable]['list']['operations'], \count($GLOBALS['TL_DCA'][$strTable]['list']['operations']), [
                'preview' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['preview'][0] ?? '',
                    'showInHeader' => true,
                    'button_callback' => function ($arrRow, $strHref, $strTitle, $_strLabel, $_strIcon, $strHtmlAttributes, $strTable) {
                        return '<a target="blank" href="' . Toolkit::getDetailPageFromEntityByIdAndTable($strTable, $arrRow['id']) . '" title="' . StringUtil::specialchars($strTitle) . '" onclick="Backend.getScrollOffset()">' . Image::getHtml('forward_2.svg') . '</a>';
                    }
                ]
            ]);
        }
    }

    protected function setFields(): void
    {
        $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['fields'] = $this->arrFields;
    }

    protected function setPalettes(): void
    {
        $strTable = $this->arrCatalog['table'];
        $objPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=? AND pid=?'],
            'value' => ['palette', '1', $this->arrCatalog['id']],
            'sorting' => 'sorting ASC'
        ]);

        if (!isset($GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'] = [];
        }

        if (!$objPalettes) {
            $GLOBALS['TL_DCA'][$strTable]['palettes']['default'] = implode(',', $this->getDefaultPalettes());
            return;
        }

        $arrPalettes = ['default' => []];
        $translator = Translation::getInstance();

        while ($objPalettes->next()) {
            $strLegend = '';
            $arrLegends = [];
            $strName = StringUtil::generateAlias(\strtolower($objPalettes->name));
            $arrFields = StringUtil::deserialize($objPalettes->fields, true);
            $arrFieldsets = StringUtil::deserialize($objPalettes->fieldsets, true);

            foreach ($arrFields as $arrField) {
                if ($arrField['field'] === '__FIELDSET__') {
                    $arrFieldset = \current($arrFieldsets);
                    if ($arrFieldset) {
                        $strLegend = StringUtil::generateAlias($arrFieldset['label']) . '_legend';
                        $GLOBALS['TL_LANG'][$strTable][$strLegend] = $translator->translate(($strTable ? $strTable . '.' : '') . 'fieldset.' . $strLegend, $arrFieldset['label']);
                        $strLegend .= ($arrFieldset['hide'] ? ':hide' : '');
                    }
                    \next($arrFieldsets);
                    continue;
                }

                if (!isset($arrLegends[$strLegend])) {
                    $arrLegends[$strLegend] = [];
                }

                $strField = $arrField['field'];
                if (\is_numeric($strField)) {
                    // Performance-Fix: RAM-Lookup statt relationalem DB-Query
                    $strField = self::$arrFieldIdToNameMap[(int)$strField] ?? null;
                    if ($strField === null) {
                        continue;
                    }
                }

                $arrLegends[$strLegend][] = $strField;
            }

            $strLegendFields = '';
            foreach ($arrLegends as $strLegendKey => $arrFieldsList) {
                if (!$strLegendKey) {
                    $strLegendFields .= \implode(',', $arrFieldsList) . ';';
                } else {
                    $strLegendFields .= '{' . $strLegendKey . '},' . implode(',', $arrFieldsList) . ';';
                }
            }

            if (empty($arrPalettes['default'])) {
                $strName = 'default';
            }

            if ($objPalettes->selector_type) {
                if (!\in_array('type', $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'], true)) {
                    $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'][] = 'type';
                }
                $strName = $objPalettes->selector_type;
            }

            $GLOBALS['TL_DCA'][$strTable]['palettes'][$strName] = $strLegendFields;
        }
    }

    protected function addSubmitOnChange($strField): void
    {
        if (isset($this->arrFields[$strField]['eval'])) {
            $this->arrFields[$strField]['eval']['submitOnChange'] = true;
        }
    }

    protected function getDefaultPalettes(): array
    {
        $arrReturn = [];
        $blnEnableVisibility = (bool)$this->arrCatalog['enableVisibility'];

        foreach ($this->arrFields as $strFieldname => $arrField) {
            if (($arrField['type'] ?? '') === 'empty') {
                continue;
            }

            if (!$blnEnableVisibility && \in_array($strFieldname, ['published', 'start', 'stop'], true)) {
                continue;
            }

            $arrReturn[] = $strFieldname;
        }

        return $arrReturn;
    }

    protected function setSubPalettes(): void
    {
        $strTable = $this->arrCatalog['table'];
        $objSubPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=?'],
            'value' => ['subpalette', '1'],
            'sorting' => 'sorting ASC'
        ]);

        if (!$objSubPalettes) {
            return;
        }

        if (!isset($GLOBALS['TL_DCA'][$strTable]['subpalettes'])) {
            $GLOBALS['TL_DCA'][$strTable]['subpalettes'] = [];
        }

        while ($objSubPalettes->next()) {
            $strFieldname = $objSubPalettes->selector;
            if (\is_numeric($strFieldname)) {
                $strFieldname = self::$arrFieldIdToNameMap[(int)$strFieldname] ?? null;
                if ($strFieldname === null) {
                    continue;
                }
            }

            $arrFields = $this->getFieldsOnly(StringUtil::deserialize($objSubPalettes->fields, true));
            $strPalette = $strFieldname;
            $this->addSubmitOnChange($strFieldname);

            if (!\in_array($strFieldname, $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'], true)) {
                $GLOBALS['TL_DCA'][$strTable]['palettes']['__selector__'][] = $strFieldname;
            }

            if ($objSubPalettes->selector_option) {
                $strPalette .= '_' . $objSubPalettes->selector_option;
            }

            $GLOBALS['TL_DCA'][$strTable]['subpalettes'][$strPalette] = empty($arrFields) ? '' : implode(',', $arrFields);
        }
    }

    protected function getFieldsOnly($arrFields): array
    {
        $arrReturn = [];

        foreach ($arrFields as $arrField) {
            $strField = $arrField['field'];

            if (\is_numeric($strField)) {
                $strField = self::$arrFieldIdToNameMap[(int)$strField] ?? null;
                if ($strField === null) {
                    continue;
                }
            }

            $arrReturn[] = $strField;
        }

        return $arrReturn;
    }

    protected function setLabels(): void
    {
        $strTable = $this->arrCatalog['table'];
        $translator = Translation::getInstance(); // 1x holen

        foreach ($this->arrFields as $strFieldname => $arrField) {
            if (isset($GLOBALS['TL_LANG'][$strTable][$strFieldname])) {
                continue;
            }

            $strName = $arrField['name'] ?? '';

            $GLOBALS['TL_LANG'][$strTable][$strFieldname] = [
                $translator->translate($strTable . '.field.title.' . $strFieldname, $strName),
                $translator->translate($strTable . '.field.description' . $strFieldname, $strName)
            ];
        }
    }

    protected function generateEmptyDataContainer(): void
    {
        $strTable = $this->arrCatalog['table'] ?? '';
        if (!$strTable) {
            return;
        }

        if (($GLOBALS['TL_DCA'][$strTable]['config']['_loaded'] ?? false) === true) {
            return;
        }

        if (isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config'])) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['_modified'] = true;
        }

        if (isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config'])) {
            $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['modified'] = true;
        }

        $GLOBALS['TL_DCA'][$strTable]['config']['onsubmit_callback'][] = function (DataContainer $objDataContainer) {
            if ($objDataContainer->activeRecord) {
                Toolkit::saveAlias(Toolkit::getActiveRecordAsArrayFromDc($objDataContainer), $this->arrFields, $this->arrCatalog);
            }
        };

        $GLOBALS['TL_DCA'][$strTable]['config']['sql'] = $GLOBALS['TL_DCA'][$strTable]['config']['sql'] ?? [
            'keys' => ['id' => 'primary']
        ];

        $GLOBALS['TL_DCA'][$strTable]['list'] = $GLOBALS['TL_DCA'][$strTable]['list'] ?? [];
        $GLOBALS['TL_DCA'][$strTable]['list']['label'] = $GLOBALS['TL_DCA'][$strTable]['list']['label'] ?? [];
        $GLOBALS['TL_DCA'][$strTable]['list']['sorting'] = $GLOBALS['TL_DCA'][$strTable]['list']['sorting'] ?? [];

        $GLOBALS['TL_DCA'][$strTable]['list']['operations'] = $GLOBALS['TL_DCA'][$strTable]['list']['operations'] ?? [
            'edit' => [
                'label' => $GLOBALS['TL_LANG']['DCA']['edit'] ?? '',
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'label' => $GLOBALS['TL_LANG']['DCA']['delete'] ?? '',
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'label' => $GLOBALS['TL_LANG']['DCA']['show'] ?? '',
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ];

        $GLOBALS['TL_DCA'][$strTable]['list']['global_operations'] = $GLOBALS['TL_DCA'][$strTable]['list']['global_operations'] ?? [
            'all' => [
                'label' => $GLOBALS['TL_LANG']['DCA']['all'] ?? '',
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ];

        $GLOBALS['TL_DCA'][$strTable]['palettes'] = $GLOBALS['TL_DCA'][$strTable]['palettes'] ?? ['__selector__' => [], 'default' => ''];
        $GLOBALS['TL_DCA'][$strTable]['subpalettes'] = $GLOBALS['TL_DCA'][$strTable]['subpalettes'] ?? [];
        $GLOBALS['TL_DCA'][$strTable]['fields'] = $GLOBALS['TL_DCA'][$strTable]['fields'] ?? [];
    }

    public function getRelatedTables(): array
    {
        return $this->arrCatalog['related'] ?? [];
    }

    protected function setOperations(): void
    {
        if (empty($this->arrCatalog['ctable']) || !is_array($this->arrCatalog['ctable'])) {
            return;
        }

        $strTable = $this->arrCatalog['table'];
        $translator = Translation::getInstance();
        $operations = $GLOBALS['TL_DCA'][$strTable]['list']['operations'] ?? [];

        foreach ($this->arrCatalog['ctable'] as $strChildTable) {
            $strKey = 'child_' . $strChildTable;
            if (isset($operations[$strKey])) {
                continue;
            }

            $strTitle = '';
            $strDescription = '';

            $objCatalog = CatalogModel::findByTableOrModule($strChildTable);
            if ($objCatalog !== null) {
                $strTitle = $objCatalog->name;
                $strDescription = $objCatalog->description;
            }

            $arrOperation = [
                $strKey => [
                    'label' => [
                        $translator->translate('child_' . $strChildTable . '.title', $strTitle),
                        $translator->translate('child_' . $strChildTable . '.description', ($strDescription ?: $strTitle)),
                    ],
                    'href' => 'table=' . $strChildTable . '&sourceTable=' . $strTable,
                    'icon' => 'children.svg'
                ]
            ];

            Cache::set('sourceTable', $strTable);
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$strTable]['list']['operations'], 1, $arrOperation);
        }
    }

    public function generate(): void
    {
        if (empty($this->arrCatalog)) {
            return;
        }

        $strTable = $this->arrCatalog['table'];

        if (($GLOBALS['TL_DCA'][$strTable]['config']['_modified'] ?? false)) {
            $this->setPalettes();
            $this->setSubPalettes();
            $this->setFields();
            $this->setLabels();
        } else {
            $this->setConfig();
            $this->setList();
            $this->setOperations();
            $this->setPalettes();
            $this->setSubPalettes();
            $this->setFields();
            $this->setLabels();
        }

        if (!empty($GLOBALS['TL_HOOKS']['loadVirtualDataContainer']) && \is_array($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($strTable, $this);
            }
        }
    }
}