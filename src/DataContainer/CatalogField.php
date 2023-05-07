<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;

class CatalogField
{

    public function checkExtensions($varValue, DataContainer $dc)
    {

        $varValue = strtolower($varValue);
        $arrExtensions = StringUtil::trimsplit(',', $varValue);
        $arrUploadTypes = StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes')));
        $arrNotAllowed = array_diff($arrExtensions, $arrUploadTypes);

        if (0 !== count($arrNotAllowed)) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['forbiddenExtensions'], implode(', ', $arrNotAllowed)));
        }

        return $varValue;
    }

    public function listFields($arrRow)
    {

        return $arrRow['name'] . '<span style="color:#999;padding-left:3px">[' . $arrRow['fieldname'] . ']</span>';
    }

    public function getFieldTypes()
    {

        $arrReturn = [];
        foreach ($GLOBALS['CM_FIELDS'] as $strType) {
            $arrReturn[$strType] = $strType;
        }
        return $arrReturn;
    }

    public function getRoles(DataContainer $dc)
    {

        $arrRoles = array_keys($GLOBALS['CM_ROLES']);

        if (!$dc->activeRecord->type) {
            return $arrRoles;
        }

        switch ($dc->activeRecord->type) {
            case 'date':
                $arrDateRoles = [];
                foreach ($GLOBALS['CM_ROLES'] as $strRole => $arrRole) {
                    if ($arrRole['group'] == 'date') {
                        $arrDateRoles[] = $strRole;
                    }
                }
                return $arrDateRoles;
            case 'listWizard':
                return ['miscellaneous'];
        }

        return $arrRoles;
    }

    public function watchFieldname($strFieldname, DataContainer $objDataContainer)
    {

        $objDatabase = Database::getInstance();
        $strType = $objDataContainer->activeRecord->type;
        $arrActiveRecord = $objDataContainer->getCurrentRecord();


        if (Input::post('role')) {
            $arrActiveRecord['role'] = Input::post('role');
        }

        $strSql = Toolkit::getSql($strType, $arrActiveRecord);
        $objCatalog = CatalogModel::findByPk($objDataContainer->activeRecord->pid);
        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();

        if (!$strFieldname || $objCatalog == null) {
            throw new \Exception(sprintf('something went wrong'));
        }

        if (in_array($strFieldname, (new \Alnv\ContaoCatalogManagerBundle\Library\Catalog(null))->getDefaultFieldnames())) {
            return $strFieldname;
        }

        $strTable = $objCatalog->table;

        if ($strFieldname == $objDataContainer->activeRecord->fieldname && $objDatabase->fieldExists($strFieldname, $strTable, true)) {
            return $strFieldname;
        }

        if ($objDataContainer->activeRecord->fieldname && $strFieldname != $objDataContainer->activeRecord->fieldname) {
            if (!$objDatabaseBuilder->renameFieldname($objDataContainer->activeRecord->fieldname, $strFieldname, $strTable, $strSql)) {
                throw new \Exception(sprintf('fieldname "%s" already exists in %s.', $strFieldname, $strTable));
            }
            return $strFieldname;
        }

        if (!$objDatabaseBuilder->createFieldIfNotExist($strFieldname, $strTable, $strSql) && !$objDataContainer->activeRecord->fieldname) {
            throw new \Exception(sprintf('fieldname "%s" already exists in %s.', $strFieldname, $strTable));
        }

        return $strFieldname;
    }

    public function changeFieldType($strValue, DataContainer $objDataContainer)
    {

        if (!$objDataContainer->activeRecord->type || !$objDataContainer->activeRecord->fieldname) {
            return $strValue;
        }

        $objCatalog = CatalogModel::findByPk($objDataContainer->activeRecord->pid);
        if ($objCatalog == null) {
            return $strValue;
        }

        if (in_array($objDataContainer->activeRecord->fieldname, (new \Alnv\ContaoCatalogManagerBundle\Library\Catalog(null))->getDefaultFieldnames())) {
            return $strValue;
        }

        $strSql = Toolkit::getSql($objDataContainer->activeRecord->type, $objDataContainer->getCurrentRecord());
        (new \Alnv\ContaoCatalogManagerBundle\Library\Database())->changeFieldType($objDataContainer->activeRecord->fieldname, $objCatalog->table, $strSql);

        return $strValue;
    }

    public function getImageSizes()
    {

        $arrReturn = [];
        $objDatabase = Database::getInstance();
        $objImagesSize = $objDatabase->prepare('SELECT * FROM tl_image_size')->execute();

        if (!$objImagesSize->numRows) {
            return $arrReturn;
        }

        while ($objImagesSize->next()) {
            $arrReturn[$objImagesSize->id] = $objImagesSize->name;
        }

        return $arrReturn;
    }
}