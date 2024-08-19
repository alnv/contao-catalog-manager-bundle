<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Library\Catalog;
use Alnv\ContaoCatalogManagerBundle\Entity\Roles;
use Alnv\ContaoCatalogManagerBundle\Library\Database as LDatabase;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;

class CatalogField
{

    public function checkExtensions($varValue, DataContainer $dc): string
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

    public function listFields($arrRow): string
    {

        return $arrRow['name'] . '<span style="color:#999;padding-left:3px">[' . $arrRow['fieldname'] . ']</span>';
    }

    public function getFieldTypes(): array
    {

        $arrReturn = [];

        foreach ($GLOBALS['CM_FIELDS'] as $strType) {
            $arrReturn[$strType] = $strType;
        }

        return $arrReturn;
    }

    public function getRoles(DataContainer $dc): array
    {

        $arrRoles = (new Roles())->get();
        $arrRoleNames = array_keys($arrRoles);
        $arrActiveRecord = Toolkit::getActiveRecordAsArrayFromDc($dc);

        if (!($arrActiveRecord['type'] ?? '')) {
            return $arrRoleNames;
        }

        switch ($arrActiveRecord['type']) {
            case 'date':
                $arrDateRoles = [];
                foreach ($arrRoles as $strRole => $arrRole) {
                    if ($arrRole['group'] == 'date') {
                        $arrDateRoles[] = $strRole;
                    }
                }
                return $arrDateRoles;
            case 'listWizard':
                return ['miscellaneous'];
        }

        return $arrRoleNames;
    }

    public function watchFieldname($strFieldname, DataContainer $objDataContainer)
    {

        $objDatabase = Database::getInstance();
        $strType = $objDataContainer->activeRecord->type;
        $arrActiveRecord = Toolkit::getActiveRecordAsArrayFromDc($objDataContainer);

        if (Input::post('role')) {
            $arrActiveRecord['role'] = Input::post('role');
        }

        $strSql = Toolkit::getSql($strType, $arrActiveRecord);
        $objCatalog = CatalogModel::findByPk($objDataContainer->activeRecord->pid);
        $objDatabaseBuilder = new LDatabase();

        $objCatalogField = Database::getInstance()->prepare('SELECT * FROM tl_catalog_field WHERE fieldname=? AND id!=? AND pid=?')->execute($strFieldname, $objDataContainer->activeRecord->id, $objDataContainer->activeRecord->pid);

        if ($objCatalogField->numRows) {
            throw new \Exception(sprintf('field name "%s" already exists in %s.', $strFieldname, $objCatalog->table));
        }

        if (!$strFieldname || !$objCatalog) {
            throw new \Exception('something went wrong');
        }

        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/', $strFieldname)) {
            throw new \Exception('special characters are not allowed');
        }

        if (in_array($strFieldname, (new Catalog(null))->getDefaultFieldnames())) {
            return $strFieldname;
        }

        if ($strFieldname == $objDataContainer->activeRecord->fieldname && $objDatabase->fieldExists($strFieldname, $objCatalog->table, true)) {
            return $strFieldname;
        }

        if ($objDataContainer->activeRecord->fieldname && $strFieldname != $objDataContainer->activeRecord->fieldname) {
            if (!$objDatabaseBuilder->renameFieldname($objDataContainer->activeRecord->fieldname, $strFieldname, $objCatalog->table, $strSql)) {
                throw new \Exception(sprintf('field name "%s" already exists in %s.', $strFieldname, $objCatalog->table));
            }
            return $strFieldname;
        }

        if (!$objDatabaseBuilder->createFieldIfNotExist($strFieldname, $objCatalog->table, $strSql) && !$objDataContainer->activeRecord->fieldname) {
            // throw new \Exception(sprintf('field name "%s" already exists in %s.', $strFieldname, $objCatalog->table));
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

        if (in_array($objDataContainer->activeRecord->fieldname, (new Catalog(null))->getDefaultFieldnames())) {
            return $strValue;
        }

        $strSql = Toolkit::getSql($objDataContainer->activeRecord->type, Toolkit::getActiveRecordAsArrayFromDc($objDataContainer));
        (new LDatabase())->changeFieldType($objDataContainer->activeRecord->fieldname, $objCatalog->table, $strSql);

        return $strValue;
    }

    public function getImageSizes(): array
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