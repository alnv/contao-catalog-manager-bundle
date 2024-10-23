<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Library\Catalog as LibraryCatalog;
use Alnv\ContaoCatalogManagerBundle\Library\Database as CatalogDatabase;
use Contao\Backend;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;

class Catalog
{

    public function addIcon($arrRow, $strLabel, DataContainer $objDataContainer = null, $strAttributes = '', $blnReturnImage = false, $blnProtected = false): string
    {

        $strIcon = 'bundles/alnvcontaocatalogmanager/icons/' . ($arrRow['pid'] ? 'sub' : '') . 'module-icon.svg';
        $strAttributes .= 'class="resize-image"';

        return Image::getHtml($strIcon, $strLabel, $strAttributes) . ' ' . $strLabel . '<span style="color:#999;padding-left:3px">[' . $arrRow['table'] . ']</span>';
    }

    public function checkAiBundle(): void
    {

        $arrBundles = System::getContainer()->get('kernel')->getBundles();

        if (!($arrBundles['AlnvContaoOpenAiAssistantBundle'] ?? '')) {
            unset($GLOBALS['TL_DCA']['tl_catalog']['list']['global_operations']['vector_files']);
        }
    }

    public function checkLicense(): void
    {
        $strInfo = "Sie verwenden aktuell die uneingeschränkte Testversion. Sobald Ihr Projekt abgeschlossen ist, können Sie unter https://shop.catalog-manager.org/ eine Lizenz erwerben. Mit dem Kauf einer Lizenz unterstützen Sie das Projekt und helfen dabei, dessen Weiterentwicklung zu fördern.";
        $strLicense = Config::get('cmLicense') ?: '';

        if (!$strLicense) {
            Message::addInfo($strInfo);
        }
    }

    public function getCatalogTypes(): array
    {
        return \array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['type']);
    }

    public function getSortingTypes(): array
    {
        return \array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['sortingType']);
    }

    public function getCutOperationButton($arrRow, $href, $strLabel, $strTitle, $strIcon, $attributes): string
    {

        if (!$arrRow['table']) {
            return '';
        }

        $objEntities = Database::getInstance()->prepare('SELECT * FROM ' . $arrRow['table'])->limit(1)->execute();
        $objPid = Database::getInstance()->prepare('SELECT * FROM tl_catalog_field WHERE pid=? AND fieldname=? AND published=?')->limit(1)->execute($arrRow['id'], 'pid', '1');

        if ($objEntities->numRows && !$objPid->numRows) {
            return '<a title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_catalog']['cutEmptyHint']) . '">' . Image::getHtml(\preg_replace('/\.svg$/i', '_.svg', $strIcon)) . '</a>';
        }

        return '<a href="' . Backend::addToUrl($href . '&amp;id=' . $arrRow['id']) . '" title="' . StringUtil::specialchars($strTitle) . '"' . $attributes . '>' . Image::getHtml($strIcon, $strLabel) . '</a> ';
    }

    public function getDataContainers(): array
    {
        return $GLOBALS['CM_DATA_CONTAINERS'];
    }

    public function getModes(DataContainer $objDataContainer): array
    {

        $arrModes = array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['mode']);

        if (!$objDataContainer->activeRecord->pid) {

            if (($intPos = array_search('parent', $arrModes)) !== false) {
                unset($arrModes[$intPos]);
            }
        } else {

            if (($intPos = array_search('tree', $arrModes)) !== false) {

                unset($arrModes[$intPos]);
            }
        }

        return array_values($arrModes);
    }

    public function getFlags(): array
    {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    }

    public function getParentFields(DataContainer $objDataContainer): array
    {

        if (!$objDataContainer->activeRecord->pid) {
            return [];
        }

        $objCatalog = new LibraryCatalog($objDataContainer->activeRecord->pid);

        return $objCatalog->getNaturalFields();
    }

    public function getFields($objDataContainer = null): array
    {

        if ($objDataContainer === null) {
            return [];
        }

        if (!$objDataContainer->activeRecord->table) {
            return [];
        }

        $objCatalog = new LibraryCatalog($objDataContainer->activeRecord->table);

        return $objCatalog->getNaturalFields();
    }

    public function setPalette(): void
    {
        if ((Input::get('act') == '' && Input::get('key') == '') || 'select' === Input::get('act')) {
            return;
        }

        $intId = (int)Input::get('id');
        $objActiveRecord = Database::getInstance()->prepare('SELECT * FROM tl_catalog WHERE id=?')->limit(1)->execute($intId);

        if (!$objActiveRecord->numRows) {
            return;
        }

        if (!($GLOBALS['TL_DCA'][$objActiveRecord->table]['config']['_modified'] ?? false)) {
            return;
        }

        PaletteManipulator::create()->removeField('enablePreview')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('validAliasCharacters')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('dataContainer')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('description')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('name')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('mode')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('enableCopy')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('enableVisibility')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('enablePanel')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('enableContentElements')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('navigation')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('position')->applyToPalette('catalog', 'tl_catalog');
        PaletteManipulator::create()->removeField('enableGeocoding')->applyToPalette('catalog', 'tl_catalog');
    }

    public function generateModulename(DataContainer $objDataContainer)
    {

        if (!$objDataContainer->activeRecord->table) {
            return null;
        }

        $strModule = Toolkit::getModuleNameByTable($objDataContainer->activeRecord->table) ?: 'module_' . strtolower($objDataContainer->activeRecord->table);

        Database::getInstance()->prepare('UPDATE ' . $objDataContainer->table . ' %s WHERE id=?')->set(['tstamp' => time(), 'module' => $strModule])->execute($objDataContainer->id);
    }

    public function getNavigation(): array
    {

        $arrReturn = [];
        if (!is_array($GLOBALS['BE_MOD']) || empty($GLOBALS['BE_MOD'])) {
            return $arrReturn;
        }

        foreach ($GLOBALS['BE_MOD'] as $strModulename => $arrModules) {
            $strModuleLabel = $GLOBALS['TL_LANG']['MOD'][$strModulename] ?: $strModulename;
            $arrReturn[$strModulename] = $strModuleLabel;
        }

        return $arrReturn;
    }

    public function watchTable($strTable, DataContainer $objDataContainer)
    {

        $objDatabaseBuilder = new CatalogDatabase();

        if (!$strTable) {
            return '';
        }

        if ($strTable == $objDataContainer->activeRecord->table && Database::getInstance()->tableExists($strTable, true)) {
            return $strTable;
        }

        if ($strTable != $objDataContainer->activeRecord->table && $objDataContainer->activeRecord->table) {
            if (!$objDatabaseBuilder->renameTable($objDataContainer->activeRecord->table, $strTable)) {
                throw new \Exception(sprintf('table "%s" already exists in catalog manager.', $strTable));
            }
        }

        $objDatabaseBuilder->createTableIfNotExist($strTable);

        return $strTable;
    }

    public function createCustomFields(DataContainer $objDataContainer)
    {

        if (!$objDataContainer->activeRecord->table) {
            return null;
        }

        $objDatabaseBuilder = new CatalogDatabase();
        $objDatabaseBuilder->createCustomFieldsIfNotExists($objDataContainer->activeRecord->table);
    }

    public function deleteTable(DataContainer $objDataContainer)
    {

        if (!$objDataContainer->activeRecord->table) {
            return null;
        }

        if (($GLOBALS['TL_DCA'][$objDataContainer->activeRecord->table]['config']['_modified'] ?? false)) {
            return;
        }

        $objDatabaseBuilder = new CatalogDatabase();
        $objDatabaseBuilder->deleteTable($objDataContainer->activeRecord->table);
    }

    public function getOrderByStatements(): array
    {
        return ['ASC', 'DESC'];
    }

    public function getTables(): array
    {
        return Database::getInstance()->listTables();
    }

    public function getDbFields(DataContainer $dc): array
    {

        $arrReturn = [];

        if ($dc->activeRecord === null || !$dc->activeRecord->dbTable) {
            return $arrReturn;
        }

        System::loadLanguageFile($dc->activeRecord->dbTable);
        Controller::loadDataContainer($dc->activeRecord->dbTable);

        foreach ($GLOBALS['TL_DCA'][$dc->activeRecord->dbTable]['fields'] as $strField => $arrField) {
            $arrReturn[$strField] = (is_array($arrField['label']) && isset($arrField['label'][0])) ? $arrField['label'][0] : $strField;
        }

        return $arrReturn;
    }

    public function getOperators(): array
    {
        return \array_keys($GLOBALS['CM_OPERATORS']);
    }
}