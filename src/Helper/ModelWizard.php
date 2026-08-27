<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\DynModel;
use Contao\Model;

class ModelWizard
{

    protected ?Model $objModel = null;

    public function __construct($table)
    {
        $GLOBALS['CM_TEMP_MODEL_TABLE'] = $table;

        if ($model = ($GLOBALS['TL_MODELS'][$table] ?? '')) {
            $this->objModel = new $model();
            return;
        }

        $strGlobalModel = $GLOBALS['CM_MODELS'][$table] ?? DynModel::class;
        $this->objModel = (new $strGlobalModel())->createDynTable($table);
        $GLOBALS['TL_MODELS'][$table] = $strGlobalModel;
    }

    public function getModel(): ?Model
    {
        return $this->objModel;
    }
}