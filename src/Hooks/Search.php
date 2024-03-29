<?php

namespace Alnv\ContaoCatalogManagerBundle\Hooks;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoCatalogManagerBundle\Views\Listing;

class Search {

    public function getSearchablePagesByPagesRoles($arrPages, $intRoot=0, $blnIsSitemap=false) {

        $objCatalogFields = \Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel::findAll([
            'column' => ['tl_catalog_field.role=?'],
            'value' => ['pages']
        ]);
        if ($objCatalogFields === null) {
            return $arrPages;
        }

        $strDNS = '';
        if ($objRoot = \PageModel::findByPk($intRoot)) {
            $strDNS = $objRoot->dns?:'';
        }

        while ($objCatalogFields->next()) {

            $strFieldname = $objCatalogFields->fieldname;

            if (!$strFieldname) {
                continue;
            }

            $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findAll(['tl_catalog.id=?'], [$objCatalogFields->pid]);

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
                            if ($strDNS) {
                                if (strpos($arrUrl['absolute'], $strDNS) !== false) {
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

    public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false, $strLanguage='') {

        $objDatabase = \Database::getInstance();
        $objModules = $objDatabase->prepare('SELECT * FROM tl_module WHERE `type`=? AND cmMaster=?')->execute('listing','1');

        if (!$objModules->numRows) {
            return $arrPages;
        }

        $strDNS = '';
        if ($objRoot = \PageModel::findByPk($intRoot)) {
            $strDNS = $objRoot->dns?:'';
        }

        while ($objModules->next()) {

            $strTable = $objModules->cmTable;
            $strPage = $objModules->cmMasterPage;

            if (!$strPage) {
                continue;
            }

            $objPage = \PageModel::findWithDetails($strPage);
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
                'column' => isset($arrFilter['column']) ? $arrFilter['column'] : null,
                'value' => isset($arrFilter['value']) ? $arrFilter['value'] : null
            ]);


            if ($objEntities) {

                while ($objEntities->next()) {

                    $strAlias = $objEntities->alias;

                    if (!$strAlias) {
                        continue;
                    }

                    $strUrl = $objPage->getAbsoluteUrl('/'.$strAlias);

                    if ($strDNS) {
                        if (strpos($strUrl, $strDNS) !== false) {
                            $arrPages[] = $strUrl;
                        }
                    } else {
                        $arrPages[] = $strUrl;
                    }
                }
            }
        }

        return $arrPages;
    }

    protected function parseFilter($objModules) {

        $arrReturn = [
            'column' => [],
            'value' => []
        ];

        if ($objModules->cmFilter) {
            switch ($objModules->cmFilterType) {
                case 'wizard':
                    \Controller::loadDataContainer($objModules->cmTable);
                    $arrQueries = Toolkit::convertComboWizardToModelValues($objModules->cmWizardFilterSettings, $GLOBALS['TL_DCA'][$objModules->cmTable]['config']['_table']);
                    $arrReturn['column'] = $arrQueries['column'];
                    $arrReturn['value'] = $arrQueries['value'];
                    break;
                case 'expert':
                    $objModules->cmValue = \Controller::replaceInsertTags($objModules->cmValue);
                    $arrReturn['column'] = explode(';', \StringUtil::decodeEntities($objModules->cmColumn));
                    $arrReturn['value'] = explode(';', \StringUtil::decodeEntities($objModules->cmValue));
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