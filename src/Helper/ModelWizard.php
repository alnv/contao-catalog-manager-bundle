<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Contao\Model;

class ModelWizard
{
    protected ?Model $objModel = null;

    public function __construct(string $table)
    {
        $strModel = '';
        try {
            $strModel = Model::getClassFromTable($table);
        } catch (\Exception $error) {
        }

        if (str_contains($strModel, 'Alnv\ContaoCatalogManagerMultilingualAdapterBundle\Models')) {
            $strModel = '';
        }

        if ($strModel && $this->modelExist($strModel)) {
            $this->objModel = new $strModel();
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
        if (str_contains($strModel, 'Alnv\ContaoCatalogManagerBundle\Models')) {
            return false;
        }

        return class_exists($strModel);
    }
}