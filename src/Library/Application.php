<?php

declare(strict_types=1);

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\ArrayUtil;
use Contao\Input;

class Application
{
    private array $initializedTables = [];

    public function initializeBackendModules(): void
    {
        $catalogCollection = new CatalogCollection();
        $catalogs = $catalogCollection->getCatalogs('catalog');

        if (empty($catalogs)) {
            return;
        }

        foreach ($catalogs as $catalog) {
            if (empty($catalog['navigation'])) {
                continue;
            }

            $moduleKey = $catalog['module'];
            $moduleConfig = [$moduleKey => $this->generateBeModConfig($catalog)];
            $position = (int)($catalog['position'] ?? 0);

            ArrayUtil::arrayInsert($GLOBALS['BE_MOD'][$catalog['navigation']], $position, $moduleConfig);
        }
    }

    public function generateBeModConfig(array $catalog): array
    {
        $tables = [$catalog['table']];

        if (isset($catalog['related']) && \is_array($catalog['related'])) {
            foreach ($catalog['related'] as $strTable) {
                if (\is_string($strTable) && $strTable !== '') {
                    $tables[] = $strTable;
                }
            }
        }

        $translator = Translation::getInstance();

        if (!isset($GLOBALS['TL_LANG']['MOD'][$catalog['module']])) {
            $GLOBALS['TL_LANG']['MOD'][$catalog['module']] = [
                $translator->translate($catalog['module'], $catalog['name'] ?? ''),
                $translator->translate($catalog['module'] . '.description', $catalog['description'] ?? ''),
            ];
        }

        if (isset($catalog['table'])) {
            $GLOBALS['TL_LANG'][$catalog['table']]['tableLabel'] = $translator->translate(
                $catalog['table'] . '.tableLabel',
                $catalog['name'] ?? ''
            );
        }

        $beModule = [
            'name'   => $catalog['module'],
            'tables' => $tables,
        ];

        if (\in_array('tl_content', $tables, true)) {
            $beModule['table'] = [BackendCsvImportController::class, 'importTableWizardAction'];
            $beModule['list']  = [BackendCsvImportController::class, 'importListWizardAction'];
        }

        return $beModule;
    }

    public function initializeDataContainerArrays(): void
    {
        $module = Input::get('do');
        if (!$module) {
            return;
        }

        $this->initializedTables = [];
        $this->initializeDataContainerArrayByTable($module);
    }

    public function initializeDataContainerArrayByTable(string $table): void
    {
        if ($table === 'catalog-manager') {
            $action = Input::get('act');
            $key = Input::get('key');

            if (($action === '' && $key === '') || $action === 'select') {
                return;
            }

            $table = Input::get('id') ?: $table;
        }

        if (isset($this->initializedTables[$table])) {
            return;
        }

        $this->initializedTables[$table] = true;

        $vDataContainerArray = new VirtualDataContainerArray($table);
        $vDataContainerArray->generate();
        $relatedTables = $vDataContainerArray->getRelatedTables();

        foreach ($relatedTables as $relatedTable) {
            if (\is_string($relatedTable) && $relatedTable !== '') {
                $this->initializeDataContainerArrayByTable($relatedTable);
            }
        }

        new ModelWizard($table);
    }
}