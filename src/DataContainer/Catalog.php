<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Library\Catalog as LibraryCatalog;
use Alnv\ContaoCatalogManagerBundle\Library\Database as CatalogDatabase;
use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
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

    public function getCatalogTypes(): array
    {

        return array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['type']);
    }

    public function getSortingTypes(): array
    {

        return \array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['sortingType']);
    }

    public function getCutOperationButton($arrRow, $href, $strLabel, $strTitle, $strIcon, $attributes)
    {

        if (!$arrRow['table']) {
            return '';
        }

        $objEntities = Database::getInstance()->prepare('SELECT * FROM ' . $arrRow['table'])->limit(1)->execute();
        $objPid = Database::getInstance()->prepare('SELECT * FROM tl_catalog_field WHERE pid=? AND fieldname=? AND published=?')->limit(1)->execute($arrRow['id'], 'pid', '1');

        if ($objEntities->numRows && !$objPid->numRows) {
            return '<a title="' . StringUtil::specialchars($GLOBALS['TL_LANG']['tl_catalog']['cutEmptyHint']) . '">' . Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $strIcon)) . '</a>';
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

    public function getFields($objDataContainer = null)
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

    public function generateModulename(DataContainer $objDataContainer)
    {

        if ($objDataContainer->activeRecord->type !== 'catalog' || !$objDataContainer->activeRecord->table) {
            return null;
        }

        $strModulename = 'module_' . strtolower($objDataContainer->activeRecord->table);
        Database::getInstance()->prepare('UPDATE ' . $objDataContainer->table . ' %s WHERE id=?')->set(['tstamp' => time(), 'module' => $strModulename])->execute($objDataContainer->id);
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

        if (!$objDatabaseBuilder->createTableIfNotExist($strTable)) {

            throw new \Exception(sprintf('table "%s" already exists in catalog manager.', $strTable));
        }

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

        $objDatabaseBuilder = new CatalogDatabase();
        $objDatabaseBuilder->deleteTable($objDataContainer->activeRecord->table);
    }

    public function getOrderByStatements(): array
    {
        return [
            'ASC',
            'DESC'
        ];
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
        return array_keys($GLOBALS['CM_OPERATORS']);
    }
}