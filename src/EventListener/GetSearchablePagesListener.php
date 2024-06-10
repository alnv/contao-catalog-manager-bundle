<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Controller;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;

class GetSearchablePagesListener
{
    public function __invoke(array $arrPages, $intRoot = 0, bool $blnIsSitemap = false, string $language = null): array
    {

        $arrPages = $this->getSearchablePages($arrPages, $intRoot, $blnIsSitemap, $language);

        return $this->getSearchablePagesByPagesRoles($arrPages, $intRoot, $blnIsSitemap, $language);
    }

    public function getSearchablePagesByPagesRoles($arrPages, $intRoot = 0, $blnIsSitemap = false, $language = null)
    {

        $objCatalogFields = CatalogFieldModel::findAll([
            'column' => ['tl_catalog_field.role=?'],
            'value' => ['pages']
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

            $objCatalog = CatalogModel::findAll([
                'column' => ['tl_catalog.id=?'],
                'value' => [$objCatalogFields->pid]
            ]);

            if ($objCatalog === null) {
                continue;
            }

            $strTable = $objCatalog->table;
            if (!$strTable) {
                continue;
            }

            $objModel = new ModelWizard($strTable);
            $objModel = $objModel->getModel();
            $objEntities = $objModel->findAll();

            if ($objEntities) {

                while ($objEntities->next()) {

                    $arrEntity = $objEntities->row();

                    if (is_array($arrEntity[$strFieldname]) && !empty($arrEntity[$strFieldname])) {

                        foreach ($arrEntity[$strFieldname] as $arrUrl) {
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

            if ($objPage->language != $strLanguage) {
                continue;
            }

            $arrFilter = $this->parseFilter($objModules);
            $objModel = new ModelWizard($strTable);
            $objModel = $objModel->getModel();
            $objEntities = $objModel->findAll([
                'language' => $objPage->language,
                'column' => $arrFilter['column'] ?? null,
                'value' => $arrFilter['value'] ?? null
            ]);

            if (!$objEntities) {
                return $arrPages;
            }

            while ($objEntities->next()) {

                if (!$objEntities->alias) {
                    continue;
                }

                $strUrl = $objPage->getAbsoluteUrl('/' . $objEntities->alias);

                if ($strDns) {
                    if (strpos($strUrl, $strDns) !== false) {
                        $arrPages[] = $strUrl;
                    }
                } else {
                    $arrPages[] = $strUrl;
                }
            }
        }

        return $arrPages;
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
                    $objModules->cmValue = Toolkit::replaceInsertTags($objModules->cmValue);
                    $arrReturn['column'] = explode(';', StringUtil::decodeEntities($objModules->cmColumn));
                    $arrReturn['value'] = explode(';', StringUtil::decodeEntities($objModules->cmValue));
                    if ((is_array($arrReturn['value']) && !empty($arrReturn['value']))) {
                        $intIndex = -1;
                        $arrReturn['value'] = array_filter($arrReturn['value'], function ($strValue) use (&$intIndex, &$arrReturn) {
                            $intIndex = $intIndex + 1;
                            if ($strValue === '' || $strValue === null) {
                                unset($arrReturn['column'][$intIndex]);
                                return false;
                            }
                            return true;
                        });
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
