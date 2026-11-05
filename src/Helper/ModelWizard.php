<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Contao\Model;

class ModelWizard
{
    protected ?Model $objModel = null;

    public function __construct(string $table)
    {

        $GLOBALS['CM_TEMP_MODEL_TABLE'] = $table;

        if ($model = $GLOBALS['TL_MODELS'][$table] ?? '') {
            $this->objModel = new $model();
            return;
        }

        $strGlobalModel = $GLOBALS['CM_MODELS'][$table] ?? '';
        if ($strGlobalModel && $this->modelExist($strGlobalModel)) {
            $this->objModel = $strGlobalModel::createDynTable($table);
            $GLOBALS['TL_MODELS'][$table] = $strGlobalModel;
            return;
        }

        $this->objModel = DynModel::createDynTable($table);
        $GLOBALS['TL_MODELS'][$table] = DynModel::class;
    }

    public function getModel(): ?Model
    {
        return $this->objModel;
    }

    protected function modelExist(string $strModel): bool
    {
        return \class_exists($strModel);
    }
}