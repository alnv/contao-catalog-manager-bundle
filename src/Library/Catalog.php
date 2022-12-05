<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;

class Catalog extends CatalogWizard {

    protected $arrFields = [];
    protected $arrCatalog = [];
    protected $strIdentifier = null;

    public function __construct($strIdentifier) {

        if ($strIdentifier === null) {
            return null;
        }

        $this->strIdentifier = $strIdentifier;
        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ($objCatalog === null) {
            return null;
        }

        $this->setCustomFields();
        $this->arrCatalog = $this->parseCatalog($objCatalog->row());
        $objFields = CatalogFieldModel::findAll([
            'column' => ['pid=?', 'published=?'],
            'value' => [$this->arrCatalog['id'], '1'],
            'order' => 'sorting ASC'
        ]);

        if ($objFields === null) {
            return null;
        }

        while ($objFields->next()) {

            $arrField = $this->parseField($objFields->row(), $this->arrCatalog);

            if ($arrField === null) {
                continue;
            }

            $this->arrFields[$objFields->fieldname] = $arrField;
        }

        $this->setDefaultFields();
        return parent::__construct();
    }

    public function getCatalog() {

        return $this->arrCatalog;
    }

    public function getFields() {

        return $this->arrFields;
    }

    public function getNaturalFields($blnLabelOnly = true) {

        $arrReturn = [];

        foreach ($this->arrFields as $strFieldname => $arrField) {
            $arrReturn[ $strFieldname ] = $blnLabelOnly ? \StringUtil::decodeEntities($arrField['label'][0]) : $strFieldname;
        }

        return $arrReturn;
    }

    protected function setDefaultFields() {
        foreach ($this->getDefaultFields() as $strAlias => $arrField) {
            if (isset($this->arrFields[$strAlias])) {
                continue;
            }
            $this->arrFields[$strAlias] = $arrField;
        }
    }

    protected function setCustomFields() {

        if ( !is_array($GLOBALS['CM_CUSTOM_FIELDS']) || empty($GLOBALS['CM_CUSTOM_FIELDS'])) {

            return null;
        }

        $arrFields = [];

        foreach ($GLOBALS['CM_CUSTOM_FIELDS'] as $strFieldname => $arrField) {
            if (isset($arrField['table']) && $this->arrCatalog['table'] != $arrField['table']) {
                continue;
            }

            $strTable = $this->arrCatalog['table'] ?? '';
            $arrLangSets = $GLOBALS['TL_LANG']['MSC'][$strFieldname] ?? [];

            unset($arrField['index']);

            if (!isset($arrField['label'])) {
                $arrField['label'] = [
                    Translation::getInstance()->translate(($strTable?$strTable.'.':'') . 'field.title.' . $strFieldname, $arrLangSets[0] ?? ''),
                    Translation::getInstance()->translate(($strTable?$strTable.'.':'') . 'field.description.' . $strFieldname, $arrLangSets[1] ?? '')
                ];
            }

            $arrFields[ $strFieldname ] = $arrField;
        }

        array_insert($this->arrFields, 0, $arrFields);
    }

    public function getDefaultFieldnames() {

        return array_keys($this->getDefaultFields());
    }

    public function getDefaultFields() {

        \System::loadLanguageFile('default');

        $arrReturn  = [
            'id' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.id', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('id')),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.id', '')
                ],
                'search' => true,
                'sql' => "int(10) unsigned NOT NULL auto_increment"
            ],
            'pid' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.pid', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('pid')),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.pid', '')
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'sorting' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.sorting', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('sorting')),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.sorting', '')
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'tstamp' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.tstamp', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('tstamp')),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.tstamp', '')
                ],
                'eval' => [
                    'rgxp'=>'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sorting' => true,
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'published' => [
                'label' => [
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.published', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('published')),
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description.published', '')
                ],
                'inputType' => 'checkbox',
                'eval' => [
                    'multiple' => false,
                    'doNotCopy' => true,
                    'tl_class' => 'clr'
                ],
                'filter' => true,
                'sql' => "char(1) NOT NULL default ''"
            ],
            'start' => [
                'label' => [
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.start', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('start')),
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description.start', '')
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp'=>'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'stop' => [
                'label' => [
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.stop', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('stop')),
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description.stop', '')
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp'=>'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'alias' => [
                'label' => [
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.alias', \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::getLabel('alias')),
                    Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description.alias', '')
                ],
                'eval' => [
                    'doNotCopy' => true,
                    'rgxp' => 'alias',
                    'role' => 'alias'
                ],
                'search' => true,
                'sql' => "varchar(128) NOT NULL default ''"
            ]
        ];
        if (isset($GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['ptable']) && $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['ptable']) {
            $arrReturn['pid']['relation'] = [
                'load' => 'lazy',
                'field' => 'id',
                'table' => $GLOBALS['TL_DCA'][$this->arrCatalog['table']]['config']['ptable'],
                'type' => 'belongsTo'
            ];
        }

        return $arrReturn;
    }
}