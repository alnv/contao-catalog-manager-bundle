<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Contao\Model;

class ModelWizard
{

    protected $objModel = null;

    public function __construct($strTable)
    {

        $strModel = '';

        try {
            $strModel = Model::getClassFromTable($strTable);
        } catch (\Exception $objError) {
            //
        }

        if (str_contains($strModel, 'Alnv\ContaoCatalogManagerMultilingualAdapterBundle\Models') !== false) {
            $strModel = '';
        }

        if ($strModel && $this->modelExist($strModel)) {
            $this->objModel = new $strModel();
            return null;
        }

        if (isset($GLOBALS['CM_MODELS'][$strTable]) && $this->modelExist($GLOBALS['CM_MODELS'][$strTable])) {
            $objMultilingualDynModel = new $GLOBALS['CM_MODELS'][$strTable]();
            $objMultilingualDynModel->createDynTable($strTable);
            $this->objModel = $objMultilingualDynModel;
            $GLOBALS['TL_MODELS'][$strTable] = $objMultilingualDynModel::class;
            return null;
        }

        $objDynModel = new DynModel();
        $objDynModel->createDynTable($strTable);
        $this->objModel = $objDynModel;

        $GLOBALS['TL_MODELS'][$strTable] = DynModel::class;
    }

    public function getModel()
    {

        return $this->objModel;
    }

    protected function modelExist($strModel): bool
    {

        if (str_contains($strModel, 'Alnv\ContaoCatalogManagerBundle\Models') !== false) {
            return false;
        }

        if (!class_exists($strModel)) {
            return false;
        }

        return true;
    }
}