<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\ArrayUtil;
use Contao\Input;

class Application
{

    public function initializeBackendModules(): void
    {

        $objCatalogCollection = new CatalogCollection();
        $arrCatalogs = $objCatalogCollection->getCatalogs('catalog');

        foreach ($arrCatalogs as $arrCatalog) {

            if (!$arrCatalog['navigation']) {
                continue;
            }

            $arrModule = [];
            $arrModule[$arrCatalog['module']] = $this->generateBeModConfig($arrCatalog);
            ArrayUtil::arrayInsert($GLOBALS['BE_MOD'][$arrCatalog['navigation']], $arrCatalog['position'], $arrModule);
        }
    }

    public function generateBeModConfig($arrCatalog): array
    {

        $arrTables = [$arrCatalog['table']];

        if (is_array($arrCatalog['related']) && !empty($arrCatalog['related'])) {
            foreach ($arrCatalog['related'] as $strTable) {
                $arrTables[] = $strTable;
            }
        }

        if (!isset($GLOBALS['TL_LANG']['MOD'][$arrCatalog['module']])) {
            $GLOBALS['TL_LANG']['MOD'][$arrCatalog['module']] = [
                Translation::getInstance()->translate($arrCatalog['module'], $arrCatalog['name']),
                Translation::getInstance()->translate($arrCatalog['module'] . '.' . 'description', $arrCatalog['description']),
            ];
        }

        return [
            'name' => $arrCatalog['module'],
            'tables' => $arrTables
        ];
    }

    public function initializeDataContainerArrays(): void
    {

        $strModule = Input::get('do');

        if (!$strModule) {
            return;
        }

        $this->initializeDataContainerArrayByTable($strModule);
    }

    public function initializeDataContainerArrayByTable($strTable): void
    {

        $objVDataContainerArray = new VirtualDataContainerArray($strTable);
        $objVDataContainerArray->generate();
        $arrRelatedTables = $objVDataContainerArray->getRelatedTables();

        if (is_array($arrRelatedTables) && !empty($arrRelatedTables)) {

            foreach ($arrRelatedTables as $strTable) {

                $this->initializeDataContainerArrayByTable($strTable);
            }
        }
    }
}