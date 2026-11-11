<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Helper\CatalogTableGuard;
use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Controller;
use Contao\Database;
use Contao\Date;
use Contao\PageModel;
use Contao\Widget;

class GetSearchablePagesListener
{
    public function __invoke(array $arrPages, $intRoot = 0, bool $blnIsSitemap = false, string $language = ''): array
    {

        $arrPages = $this->getSearchablePages($arrPages, $intRoot, $blnIsSitemap, $language);

        return $this->getSearchablePagesByPagesRoles($arrPages, $intRoot, $blnIsSitemap, $language);
    }

    public function getSearchablePagesByPagesRoles($arrPages, $intRoot = 0, $blnIsSitemap = false, $language = '')
    {
        $objCatalogFields = CatalogFieldModel::findAll([
            'column' => ['tl_catalog_field.role=? OR tl_catalog_field.role=?'],
            'value' => ['pages', 'page']
        ]);

        if ($objCatalogFields === null) {
            return $arrPages;
        }

        $strDns = '';
        if ($objRoot = PageModel::findByPk($intRoot)) {
            $strDns = $objRoot->dns ?: '';
        }

        while ($objCatalogFields->next()) {
            $strFieldname = $objCatalogFields->fieldname;
            if (!$strFieldname) {
                continue;
            }

            $objCatalog = CatalogModel::findByPk($objCatalogFields->pid);

            if ($objCatalog === null) {
                continue;
            }

            $strTable = $objCatalog->table;

            if (!$strTable || !CatalogTableGuard::isValidIdentifier($strTable)) {
                continue;
            }

            Controller::loadDataContainer($strTable);

            // Nur sichtbare Einträge berücksichtigen (vorher: alle, auch unveröffentlichte)
            $arrFindOptions = [];
            if ($objCatalog->enableVisibility && Database::getInstance()->fieldExists('published', $strTable)) {
                $arrFindOptions['column'] = [$this->getVisibilityColumn(($GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?? '') ?: $strTable)];
            }

            $objModel = new ModelWizard($strTable);
            $objModel = $objModel->getModel();
            $objEntities = $objModel->findAll($arrFindOptions);

            if ($objEntities) {
                while ($objEntities->next()) {
                    $arrEntity = $objEntities->row();
                    $varPages = $arrEntity[$strFieldname] ?? '';
                    $varPages = Toolkit::parseCatalogValue($varPages, Widget::getAttributesFromDca(($GLOBALS['TL_DCA'][$strTable]['fields'][$strFieldname] ?? []), $strFieldname, $varPages, $strFieldname, $strTable), $arrEntity, false);

                    if (is_array($varPages) && !empty($varPages)) {
                        foreach ($varPages as $arrUrl) {
                            if (!isset($arrUrl['absolute'])) {
                                continue;
                            }
                            
                            if ($strDns) {
                                if (str_contains($arrUrl['absolute'], $strDns)) {
                                    $arrPages[] = $arrUrl['absolute'];
                                }
                            } else {
                                $arrPages[] = $arrUrl['absolute'];
                            }
                        }
                    }
                }
            }
        }

        return $arrPages;
    }

    public function getSearchablePages($arrPages, $intRoot = 0, $blnIsSitemap = false, $strLanguage = '')
    {

        $objModules = Database::getInstance()->prepare('SELECT * FROM tl_module WHERE `type`=? AND cmMaster=?')->execute('listing-table', '1');
        if (!$objModules->numRows) {
            return $arrPages;
        }

        $strDns = '';
        if ($objRoot = PageModel::findByPk($intRoot)) {
            $strDns = $objRoot->dns ?: '';
        }

        while ($objModules->next()) {

            $strTable = $objModules->cmTable;
            $strPage = $objModules->cmMasterPage;

            if (!$strPage) {
                continue;
            }

            $objPage = PageModel::findWithDetails($strPage);
            if (!$strTable || $objPage === null) {
                continue;
            }

            // Unveröffentlichte, geschützte oder per robots/noSearch ausgeschlossene Detailseiten
            // gehören nicht in Sitemap/Suchindex
            if (!$this->isIndexablePage($objPage, $blnIsSitemap)) {
                continue;
            }

            if ($objPage->language != $strLanguage) {
                continue;
            }

            $objCatalog = CatalogModel::findByTableOrModule($strTable);
            $blnVisibility = (bool)$objCatalog?->enableVisibility;
            $arrFilter = $this->parseFilter($objModules);


            if ($blnVisibility && Database::getInstance()->fieldExists('published', $strTable)) {
                Controller::loadDataContainer($strTable);

                if (!isset($arrFilter['column']) || !\is_array($arrFilter['column'])) {
                    $arrFilter['column'] = [];
                }

                $strTable = ($GLOBALS['TL_DCA'][$strTable]['config']['_table'] ?? '') ?: $strTable;
                $arrFilter['column'][] = $this->getVisibilityColumn($strTable);
            }

            $objModel = new ModelWizard($strTable);
            $objModel = $objModel->getModel();
            $objEntities = $objModel->findAll([
                'language' => $objPage->language,
                'column' => $arrFilter['column'] ?? null,
                'value' => $arrFilter['value'] ?? null
            ]);

            if (!$objEntities) {
                continue;
            }

            while ($objEntities->next()) {
                if (!$objEntities->alias) {
                    continue;
                }

                $strUrl = Toolkit::parseDetailLink($objPage->row(), $objEntities->alias, $objEntities->row(), true);

                if ($strDns) {
                    if (\strpos($strUrl, $strDns) !== false) {
                        $arrPages[] = $strUrl;
                    }
                } else {
                    $arrPages[] = $strUrl;
                }
            }
        }

        return $arrPages;
    }

    protected function getVisibilityColumn(string $strTable): string
    {
        $intTime = Date::floorToMinute();
        $strQuoted = Database::quoteIdentifier($strTable);

        return sprintf(
            "(%1\$s.start='' OR %1\$s.start<='%2\$d') AND (%1\$s.stop='' OR %1\$s.stop>'%3\$d') AND %1\$s.published='1'",
            $strQuoted,
            $intTime,
            $intTime + 60
        );
    }

    protected function isIndexablePage(PageModel $objPage, bool $blnIsSitemap): bool
    {
        $intTime = time();

        if (!$objPage->published || ($objPage->start && $objPage->start > $intTime) || ($objPage->stop && $objPage->stop <= $intTime)) {
            return false;
        }

        // Geschützte Seiten (Mitgliederbereich) nicht veröffentlichen – wie Contaos eigener Sitemap-Generator
        if ($objPage->protected) {
            return false;
        }

        if ($blnIsSitemap) {
            return !\str_starts_with((string)$objPage->robots, 'noindex');
        }

        return !$objPage->noSearch;
    }

    protected function parseFilter($objModules): array
    {

        $arrReturn = ['column' => [], 'value' => []];

        if ($objModules->cmFilter) {
            switch ($objModules->cmFilterType) {
                case 'wizard':
                    Controller::loadDataContainer($objModules->cmTable);
                    $arrQueries = Toolkit::convertComboWizardToModelValues($objModules->cmWizardFilterSettings, $GLOBALS['TL_DCA'][$objModules->cmTable]['config']['_table']);
                    $arrReturn['column'] = $arrQueries['column'];
                    $arrReturn['value'] = $arrQueries['value'];
                    break;
                case 'expert':
                    foreach (Toolkit::convertExpertQueries(($objModules->cmColumn ?: ''), ($objModules->cmValue ?: '')) as $strKey => $strValue) {
                        $arrReturn[$strKey] = $strValue;
                    }
                    break;
            }
        }

        if (empty($arrReturn['value'])) {
            unset($arrReturn['value']);
            unset($arrReturn['column']);
        }

        return $arrReturn;
    }
}
