<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Controller;
use Contao\StringUtil;
use Contao\System;

class Catalog extends CatalogWizard
{
    protected static array $runtimeCache = [];

    protected array $arrFields = [];
    protected array $arrCatalog = [];
    protected string|null $strIdentifier = null;

    public function __construct(?string $strIdentifier)
    {
        if ($strIdentifier === null) {
            return;
        }

        $this->strIdentifier = $strIdentifier;

        if (isset(self::$runtimeCache[$strIdentifier])) {
            $this->arrCatalog = self::$runtimeCache[$strIdentifier]['catalog'];
            $this->arrFields = self::$runtimeCache[$strIdentifier]['fields'];
            return;
        }

        $objCatalog = CatalogModel::findByTableOrModule($this->strIdentifier);

        if ($objCatalog === null) {
            return;
        }

        $this->arrCatalog = $this->parseCatalog($objCatalog->row());

        $this->setCustomFields();
        $this->setAllFields();
        $this->setDefaultFields();

        self::$runtimeCache[$strIdentifier] = [
            'catalog' => $this->arrCatalog,
            'fields' => $this->arrFields
        ];
    }

    protected function setAllFields(): void
    {
        $strTable = $this->arrCatalog['table'] ?? '';
        if (!$strTable) {
            return;
        }

        Controller::loadDataContainer($strTable);
        Controller::loadLanguageFile($strTable);

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] ?? [] as $strField => $arrField) {
            $arrField['label'] = $arrField['label'] ?? [$strField, ''];
            $this->arrFields[$strField] = $arrField;
        }

        $objFields = CatalogFieldModel::findAll([
            'column' => ['pid=?', 'published=?'],
            'value' => [$this->arrCatalog['id'], '1'],
            'order' => 'sorting ASC'
        ]);

        if (!$objFields) {
            return;
        }

        while ($objFields->next()) {
            $arrField = $this->parseField($objFields->row(), $this->arrCatalog);

            if ($arrField === null) {
                continue;
            }

            $this->arrFields[$objFields->fieldname] = $arrField;
        }
    }

    public function getCatalog(): array
    {
        return $this->arrCatalog;
    }

    public function getFields(): array
    {
        return $this->arrFields;
    }

    public function getNaturalFields($blnLabelOnly = true): array
    {
        $arrReturn = [];

        foreach ($this->arrFields as $strFieldname => $arrField) {
            $arrReturn[$strFieldname] = $blnLabelOnly ? StringUtil::decodeEntities($arrField['label'][0] ?? '') : $strFieldname;
        }

        return $arrReturn;
    }

    protected function setDefaultFields(): void
    {
        // Performance-Fix: Methode nur 1x aufrufen statt in jedem Schleifendurchlauf
        $defaultFields = $this->getDefaultFields();

        foreach ($defaultFields as $strAlias => $arrField) {
            if (isset($this->arrFields[$strAlias])) {
                continue;
            }
            $this->arrFields[$strAlias] = $arrField;
        }
    }

    protected function setCustomFields(): void
    {
        if (empty($GLOBALS['CM_CUSTOM_FIELDS']) || !is_array($GLOBALS['CM_CUSTOM_FIELDS'])) {
            return;
        }

        $arrFields = [];
        $strTable = $this->arrCatalog['table'] ?? '';
        $translator = Translation::getInstance(); // 1x holen statt in der Schleife

        foreach ($GLOBALS['CM_CUSTOM_FIELDS'] as $strFieldname => $arrField) {
            if (isset($arrField['table']) && $strTable !== $arrField['table']) {
                continue;
            }

            $arrLangSets = $GLOBALS['TL_LANG']['MSC'][$strFieldname] ?? [];
            unset($arrField['index']);

            if (!isset($arrField['label'])) {
                $arrField['label'] = [
                    $translator->translate(($strTable ? $strTable . '.' : '') . 'field.title.' . $strFieldname, $arrLangSets[0] ?? ''),
                    $translator->translate(($strTable ? $strTable . '.' : '') . 'field.description.' . $strFieldname, $arrLangSets[1] ?? '')
                ];
            }

            $arrFields[$strFieldname] = $arrField;
        }

        ArrayUtil::arrayInsert($this->arrFields, 0, $arrFields);
    }

    public function getDefaultFieldnames(): array
    {
        return array_keys($this->getDefaultFields());
    }

    public function getDefaultFields(): array
    {
        System::loadLanguageFile('default');

        $strTable = $this->arrCatalog['table'] ?? '';
        $strKeyName = ($strTable ? $strTable . '.' : '');
        $translator = Translation::getInstance();

        $arrReturn = [
            'id' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.id', Toolkit::getLabel('id')),
                    $translator->translate($strKeyName . 'field.description.id', '')
                ],
                'search' => true,
                'sql' => "int(10) unsigned NOT NULL auto_increment"
            ],
            'pid' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.pid', Toolkit::getLabel('pid')),
                    $translator->translate($strKeyName . 'field.description.pid', '')
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'sorting' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.sorting', Toolkit::getLabel('sorting')),
                    $translator->translate($strKeyName . 'field.description.sorting', '')
                ],
                'flag' => 11,
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'tstamp' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.tstamp', Toolkit::getLabel('tstamp')),
                    $translator->translate($strKeyName . 'field.description.tstamp', '')
                ],
                'eval' => [
                    'rgxp' => 'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sorting' => true,
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'published' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.published', Toolkit::getLabel('published')),
                    $translator->translate($strKeyName . 'field.description.published', '')
                ],
                'inputType' => 'checkbox',
                'eval' => [
                    'multiple' => false,
                    'doNotCopy' => true,
                    'tl_class' => 'clr'
                ],
                'toggle' => true,
                'filter' => true,
                'sql' => ['type' => 'boolean', 'default' => false]
            ],
            'start' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.start', Toolkit::getLabel('start')),
                    $translator->translate($strKeyName . 'field.description.start', '')
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp' => 'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sql' => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
            ],
            'stop' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.stop', Toolkit::getLabel('stop')),
                    $translator->translate($strKeyName . 'field.description.stop', '')
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp' => 'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sql' => "varchar(10) COLLATE ascii_bin NOT NULL default ''"
            ],
            'alias' => [
                'label' => [
                    $translator->translate($strKeyName . 'field.title.alias', Toolkit::getLabel('alias')),
                    $translator->translate($strKeyName . 'field.description.alias', '')
                ],
                'eval' => [
                    'doNotCopy' => true,
                    'rgxp' => 'alias',
                    'role' => 'alias'
                ],
                'search' => true,
                'sql' => "varchar(255) BINARY NOT NULL default ''"
            ]
        ];

        if ($strTable && isset($GLOBALS['TL_DCA'][$strTable]['config']['ptable']) && $GLOBALS['TL_DCA'][$strTable]['config']['ptable']) {
            $arrReturn['pid']['relation'] = [
                'load' => 'lazy',
                'field' => 'id',
                'table' => $GLOBALS['TL_DCA'][$strTable]['config']['ptable'],
                'type' => 'belongsTo'
            ];
        }

        return $arrReturn;
    }
}