<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogPaletteModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Backend;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class VirtualDataContainerArray
{
    protected array $arrCatalog = [];

    protected array $arrFields = [];

    public function __construct($strModule)
    {
        System::loadLanguageFile('default');

        $objCatalog = new Catalog($strModule);
        $this->arrCatalog = $objCatalog->getCatalog();
        $this->arrFields = $objCatalog->getFields();

        $this->generateEmptyDataContainer();
    }

    protected function setConfig(): void
    {
        $strTable = $this->arrCatalog['table'];

        $GLOBALS['TL_DCA'][$strTable]['config']['backendSearchIgnore'] = true;
        $GLOBALS['TL_DCA'][$strTable]['config']['_table'] = $strTable;
        $GLOBALS['TL_DCA'][$strTable]['config']['ctable'] = Toolkit::extendField(($GLOBALS['TL_DCA'][$strTable]['config']['ctable'] ?? []), ($this->arrCatalog['ctable'] ?? []));
        $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] = $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'] ?? $this->getDataContainerNamespace($this->arrCatalog['dataContainer']);

        if ($this->arrCatalog['ptable'] ?? '') {
            $GLOBALS['TL_DCA'][$strTable]['config']['ptable'] = $this->arrCatalog['ptable'];
        }

        if ($this->arrCatalog['enableGeocoding'] ?? false) {
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

            $objActiveRecord = Database::getInstance()
                ->prepare('SELECT * FROM ' . $objDataContainer->table . ' WHERE id=?')
                ->limit(1)
                ->execute($objDataContainer->id);

            if (!$objActiveRecord->numRows) {
                return;
            }

            Cache::set('activeRecord', $objActiveRecord->row());
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

            if (\in_array($this->arrCatalog['sortingType'], ['fixed', 'switchable']) && !$this->arrCatalog['showColumns']) {
                $arrList['labels']['group_callback'] = function ($strGroupValue, $strMode, $strField, $arrRecord, DataContainer $dc) {
                    try {
                        $varReturn = Toolkit::parseCatalogValue($strGroupValue, Widget::getAttributesFromDca($this->arrFields[$strField] ?? [], $strField, $strGroupValue, $strField, $dc->table), $arrRecord, true);
                    } catch (\Exception $error) {
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

        $table = $this->arrCatalog['table'];
        if ($blnUseCut && !\in_array('cut', \array_keys(($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? [])))) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$table]['list']['operations'], 1, [
                'cut' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['cut'] ?? '',
                    'icon' => 'cut.svg',
                    'href' => 'act=paste&amp;mode=cut',
                    'attributes' => 'onclick="Backend.getScrollOffset()"'
                ]
            ]);
        }

        $GLOBALS['TL_DCA'][$table]['list']['label'] = $arrList['labels'];
        $GLOBALS['TL_DCA'][$table]['list']['sorting'] = $arrList['sorting'];

        if ($this->arrCatalog['enableCopy'] && !\in_array('copy', \array_keys(($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? [])))) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$table]['list']['operations'], 1, [
                'copy' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['copy'] ?? '',
                    'href' => 'act=copy',
                    'icon' => 'copy.svg'
                ]
            ]);
        }

        if ($this->arrCatalog['enableVisibility'] && !\in_array('toggle', array_keys(($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? [])))) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$table]['list']['operations'], \count($GLOBALS['TL_DCA'][$table]['list']['operations']) - 1, [
                'toggle' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['toggle'][0] ?? '',
                    'href' => 'act=toggle&amp;field=published',
                    'icon' => 'visible.svg',
                    'showInHeader' => true
                ]
            ]);
        }

        if (($this->arrCatalog['enablePreview'] ?? false) && !\in_array('preview', \array_keys(($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? [])))) {
            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$table]['list']['operations'], \count($GLOBALS['TL_DCA'][$table]['list']['operations']), [
                'preview' => [
                    'label' => $GLOBALS['TL_LANG']['DCA']['preview'][0] ?? '',
                    'showInHeader' => true,
                    'button_callback' => function ($arrRow, $strHref, $strTitle, $_strLabel, $_strIcon, $strHtmlAttributes, $strTable) {
                        $strIcon = Image::getHtml('forward_2.svg');
                        $strUrl = Toolkit::getDetailPageFromEntityByIdAndTable($strTable, $arrRow['id']);
                        return '<a target="blank" href="' . $strUrl . '" title="' . StringUtil::specialchars($strTitle) . '" onclick="Backend.getScrollOffset()">' . $strIcon . '</a>';
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
        $objPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=? AND pid=?'],
            'value' => ['palette', '1', $this->arrCatalog['id']],
            'sorting' => 'sorting ASC'
        ]);

        $table = $this->arrCatalog['table'];
        if (!isset($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'])) {
            $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] = [];
        }

        if (!$objPalettes) {
            $GLOBALS['TL_DCA'][$table]['palettes']['default'] = implode(',', $this->getDefaultPalettes());
            return;
        }

        $arrPalettes = [
            'default' => []
        ];

        while ($objPalettes->next()) {
            $legend = '';
            $legends = [];
            $name = StringUtil::generateAlias(\strtolower($objPalettes->name));
            $fields = StringUtil::deserialize($objPalettes->fields, true);
            $fieldSets = StringUtil::deserialize($objPalettes->fieldsets, true);

            foreach ($fields as $arrField) {
                $field = $arrField['field'] ?? '';

                if ($field == '__FIELDSET__') {
                    $arrFieldset = \current($fieldSets);
                    $legend = StringUtil::generateAlias($arrFieldset['label'] ?? '') . '_legend';
                    $GLOBALS['TL_LANG'][$table][$legend] = Translation::getInstance()->translate(($table ? $table . '.' : '') . 'fieldset.' . $legend, $arrFieldset['label'] ?? '');
                    $legend .= ($arrFieldset['hide'] ? ':hide' : '');
                    \next($fieldSets);
                    continue;
                }

                if (!isset($legends[$legend])) {
                    $legends[$legend] = [];
                }

                if (\is_numeric($field)) {
                    if ($objField = CatalogFieldModel::findByPk($field)) {
                        $field = $objField->fieldname;
                    }
                }

                $legends[$legend][] = $field;
            }

            $legendFields = '';
            foreach ($legends as $legend => $arrFields) {
                if (!$legend) {
                    $legendFields .= \implode(',', $arrFields) . ';';
                } else {
                    $legendFields .= '{' . $legend . '},' . \implode(',', $arrFields) . ';';
                }
            }

            if (empty($arrPalettes['default'])) {
                $name = 'default';
            }

            if ($objPalettes->selector_type) {
                if (!\in_array('type', ($GLOBALS['TL_DCA'][$table]['palettes']['__selector__'] ?? []))) {
                    $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = 'type';
                }
                $name = $objPalettes->selector_type;
            }

            $GLOBALS['TL_DCA'][$table]['palettes'][$name] = $legendFields;
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
        $return = [];
        foreach ($this->arrFields as $strFieldname => $arrField) {
            $strType = $this->arrFields['type'] ?? '';
            if ($strType == 'empty') {
                continue;
            }

            if (!$this->arrCatalog['enableVisibility'] && \in_array($strFieldname, ['published', 'start', 'stop'])) {
                continue;
            }

            $return[] = $strFieldname;
        }

        return $return;
    }

    protected function setSubPalettes(): void
    {
        $objSubPalettes = CatalogPaletteModel::findAll([
            'column' => ['type=? AND published=?'],
            'value' => ['subpalette', '1'],
            'sorting' => 'sorting ASC'
        ]);

        if (!$objSubPalettes) {
            return;
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

            $name = isset($arrField['name']) && $arrField['name'] ? $arrField['name'] : '';
            $GLOBALS['TL_LANG'][$this->arrCatalog['table']][$strFieldname] = [
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.' . $strFieldname, $name),
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description' . $strFieldname, $name)
            ];
        }
    }

    protected function generateEmptyDataContainer(): void
    {
        $table = $this->arrCatalog['table'] ?? '';
        if (!$table) {
            return;
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config']['_loaded']) && $GLOBALS['TL_DCA'][$table]['config']['_loaded'] === true) {
            return;
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config'])) {
            $GLOBALS['TL_DCA'][$table]['config']['_modified'] = true;
        }

        if (isset($GLOBALS['TL_DCA'][$table]['config'])) {
            $GLOBALS['TL_DCA'][$table]['config']['modified'] = true;
        }

        $GLOBALS['TL_DCA'][$table] = $GLOBALS['TL_DCA'][$table] ?? [];
        $GLOBALS['TL_DCA'][$table]['config'] = $GLOBALS['TL_DCA'][$table]['config'] ?? [];
        $GLOBALS['TL_DCA'][$table]['config']['_loaded'] = true;
        $GLOBALS['TL_DCA'][$table]['config']['onsubmit_callback'][] = function (DataContainer $objDataContainer) {
            if ($objDataContainer->activeRecord) {
                Toolkit::saveAlias(Toolkit::getActiveRecordAsArrayFromDc($objDataContainer), $this->arrFields, $this->arrCatalog);
            }
        };
        $GLOBALS['TL_DCA'][$table]['config']['sql'] = $GLOBALS['TL_DCA'][$table]['config']['sql'] ?? [
            'keys' => [
                'id' => 'primary'
            ]
        ];

        $GLOBALS['TL_DCA'][$table]['list'] = $GLOBALS['TL_DCA'][$table]['list'] ?? [];
        $GLOBALS['TL_DCA'][$table]['list']['label'] = $GLOBALS['TL_DCA'][$table]['list']['label'] ?? [];
        $GLOBALS['TL_DCA'][$table]['list']['sorting'] = $GLOBALS['TL_DCA'][$table]['list']['sorting'] ?? [];
        $GLOBALS['TL_DCA'][$table]['list']['operations'] = $GLOBALS['TL_DCA'][$table]['list']['operations'] ?? [
            'edit' => [
                'primary' => true,
                'label' => $GLOBALS['TL_LANG']['DCA']['edit'] ?? [],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'primary' => true,
                'label' => $GLOBALS['TL_LANG']['DCA']['delete'] ?? [],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'primary' => true,
                'label' => $GLOBALS['TL_LANG']['DCA']['show'] ?? [],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ];

        $GLOBALS['TL_DCA'][$table]['list']['global_operations'] = $GLOBALS['TL_DCA'][$table]['list']['global_operations'] ?? [
            'all' => [
                'label' => $GLOBALS['TL_LANG']['DCA']['all'] ?? '',
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ];

        $GLOBALS['TL_DCA'][$table]['palettes'] = $GLOBALS['TL_DCA'][$table]['palettes'] ?? ['__selector__' => [], 'default' => ''];
        $GLOBALS['TL_DCA'][$table]['subpalettes'] = $GLOBALS['TL_DCA'][$table]['subpalettes'] ?? [];
        $GLOBALS['TL_DCA'][$table]['fields'] = $GLOBALS['TL_DCA'][$table]['fields'] ?? [];
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

            $strKey = 'child_' . $strTable;
            if (\in_array($strKey, \array_keys(($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'] ?? [])))) {
                continue;
            }

            $arrOperation = [];
            $arrOperation[$strKey] = [
                'label' => [
                    Translation::getInstance()->translate('child_' . $strTable . '.title', $strTitle),
                    Translation::getInstance()->translate('child_' . $strTable . '.description', ($strDescription ?: $strTitle)),
                ],
                'href' => 'table=' . $strTable . '&sourceTable=' . $this->arrCatalog['table'],
                'icon' => 'children.svg',
                'primary' => true
            ];

            Cache::set('sourceTable', $this->arrCatalog['table']);

            ArrayUtil::arrayInsert($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['list']['operations'], 1, $arrOperation);
        }
    }

    public function generate(): void
    {
        if (empty($this->arrCatalog)) {
            return;
        }

        if (($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['_modified'] ?? false)) {
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

        if (isset($GLOBALS['TL_HOOKS']['loadVirtualDataContainer']) && \is_array($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadVirtualDataContainer'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($this->arrCatalog['table'], $this);
            }
        }
    }
}