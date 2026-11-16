<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Models\MultilingualDynModel;
use Contao\StringUtil;

class MultilingualCatalogListener
{

    public function onParseCatalogField($arrField, $arrCatalogField): array
    {
        if ($arrCatalogField['translatableFor'] ?? null) {
            $arrLanguages = StringUtil::deserialize($arrCatalogField['translatableFor'], true);
            $arrField['eval']['translatableFor'] = \in_array('*', $arrLanguages) ? '*' : $arrLanguages;
        }

        return $arrField;
    }

    public function onParseCatalog($arrCatalog): array
    {
        if (($arrCatalog['dataContainer'] ?? '') == 'Multilingual') {
            $arrCatalog['_table'] = $arrCatalog['table'];
            $GLOBALS['CM_MODELS'][$arrCatalog['table']] = MultilingualDynModel::class;
        }

        return $arrCatalog;
    }
}
