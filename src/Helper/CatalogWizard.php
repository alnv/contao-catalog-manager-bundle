<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit as WidgetToolkit;
use Contao\Database;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;

abstract class CatalogWizard
{

    protected function parseCatalog($arrCatalog)
    {

        $strIdentifier = 'catalog_' . $arrCatalog['table'];

        if (Cache::has($strIdentifier)) {
            return Cache::get($strIdentifier);
        }

        $arrRelated = [];
        $arrChildren = [];
        $this->getRelatedTablesByCatalog($arrCatalog, $arrRelated, $arrChildren);
        $arrCatalog['columns'] = StringUtil::deserialize($arrCatalog['columns'], true);
        $arrCatalog['headerFields'] = StringUtil::deserialize($arrCatalog['headerFields'], true);
        $arrCatalog['order'] = WidgetToolkit::decodeJson($arrCatalog['order'], [
            'option' => 'field',
            'option2' => 'order'
        ]);
        $arrCatalog['ptable'] = '';
        $arrCatalog['related'] = $arrRelated;
        $arrCatalog['ctable'] = $arrChildren;
        $arrCatalog['_table'] = $arrCatalog['table'];

        if ($arrCatalog['pid']) {
            $arrCatalog['ptable'] = $this->getParentCatalogByPid($arrCatalog['pid']);
        }

        if ($arrCatalog['enableContentElements']) {
            $arrCatalog['ctable'][] = 'tl_content';
            $arrCatalog['related'][] = 'tl_content';
        }

        if (isset($GLOBALS['TL_HOOKS']['parseCatalog']) && is_array($GLOBALS['TL_HOOKS']['parseCatalog'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseCatalog'] as $arrCallback) {
                $arrCatalog = System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrCatalog, $this);
            }
        }

        Cache::set($strIdentifier, $arrCatalog);

        return $arrCatalog;
    }

    protected function getRelatedTablesByCatalog($arrCatalog, &$arrRelated, &$arrChildren, $intLevel = 0)
    {

        $objChildCatalogs = CatalogModel::findChildrenCatalogsById($arrCatalog['id']);

        if ($objChildCatalogs === null) {
            return null;
        }

        $blnFirstCall = !$intLevel;
        while ($objChildCatalogs->next()) {

            if ($objChildCatalogs->enableContentElements && !in_array('tl_content', $arrRelated)) {
                $arrRelated[] = 'tl_content';
            }

            if ($objChildCatalogs->table) {
                $arrRelated[] = $objChildCatalogs->table;
                if ($blnFirstCall) {
                    $arrChildren [] = $objChildCatalogs->table;
                }
            }

            $intLevel++;
            self::getRelatedTablesByCatalog($objChildCatalogs->row(), $arrRelated, $arrChildren, $intLevel);
        }
    }

    protected function getParentCatalogByPid($strPid)
    {

        $objParent = CatalogModel::findByPk($strPid);

        if ($objParent === null) {
            return '';
        }

        return $objParent->table;
    }

    public function parseField($arrField, $arrCatalog = [])
    {

        $strIdentifier = 'catalog_field_' . $arrField['id'];

        if (Cache::has($strIdentifier)) {
            return Cache::get($strIdentifier);
        }

        if (!$arrField['type']) {
            return null;
        }

        $blnMultiple = (bool)$arrField['multiple'];
        $arrField['description'] = trim(strip_tags($arrField['description']));
        $arrReturn = [
            'sorting' => !$blnMultiple,
            'name' => $arrField['name'],
            'label' => [
                Translation::getInstance()->translate(($arrCatalog['table'] ? $arrCatalog['table'] . '.' : '') . 'field.title.' . $arrField['fieldname'], $arrField['name']),
                Translation::getInstance()->translate(($arrCatalog['table'] ? $arrCatalog['table'] . '.' : '') . '.field.description.' . $arrField['fieldname'], $arrField['description']),
            ],
            'eval' => [
                'tl_class' => 'w50',
                'allowHtml' => true,
                'decodeEntities' => true,
                'multiple' => $blnMultiple,
                'role' => $arrField['role'] ?: '',
                'useAsAlias' => $arrField['useAsAlias'] ?: '',
                'mandatory' => (bool)$arrField['mandatory'],
                'size' => $arrField['size'] ? intval($arrField['size']) : 1
            ],
            'sql' => Toolkit::getSql($arrField['type'], $arrField)
        ];

        if ($arrField['includeBlankOption']) {
            $arrReturn['eval']['includeBlankOption'] = true;
            $arrReturn['eval']['blankOptionLabel'] = $arrField['blankOptionLabel'];
        }

        if (in_array($arrField['type'], ['select', 'radio', 'checkbox', 'checkboxWizard'])) {
            $arrReturn['options_callback'] = function ($objDataContainer = null) use ($arrField) {
                $objOptions = Options::getInstance($arrField['fieldname'] . '.' . $arrField['pid']);
                $objOptions::setParameter($arrField, $objDataContainer);
                return $objOptions::getOptions();
            };

            if ($arrField['optionsSource'] == 'dbOptions') {

                $strTable = '';
                if (isset($GLOBALS['TL_DCA'][$arrField['dbTable']])) {
                    $strTable = $GLOBALS['TL_DCA'][$arrField['dbTable']]['config']['_table'];
                }

                $arrReturn['relation'] = [
                    'load' => 'lazy',
                    'field' => $arrField['dbKey'],
                    'table' => $strTable ?: $arrField['dbTable'],
                    'type' => $blnMultiple ? 'hasMany' : 'hasOne'
                ];
            }

            if ($arrField['csv']) {
                $arrReturn['eval']['csv'] = ',';
            }
        }

        if ($strRgxp = Toolkit::getRgxp($arrField['type'], $arrField)) {
            $arrReturn['eval']['rgxp'] = $strRgxp;
        }

        switch ($arrField['type']) {
            case 'explanation':
                $arrReturn['inputType'] = 'explanation';
                $arrReturn['eval']['text'] = $arrField['text'];
                break;
            case 'listWizard':
                $arrReturn['inputType'] = 'listWizard';
                $arrReturn['eval']['multiple'] = true;
                $arrReturn['eval']['tl_class'] = 'clr';
                break;
            case 'text':
                $arrReturn['search'] = true;
                $arrReturn['sorting'] = true;
                $arrReturn['inputType'] = 'text';
                if ($arrReturn['eval']['multiple'] && $arrReturn['eval']['size'] > 1) {
                    $arrReturn['eval']['tl_class'] = 'long clr';
                }
                break;
            case 'date':
                $arrReturn['flag'] = 6;
                $arrReturn['sorting'] = true;
                $arrReturn['inputType'] = 'text';
                $arrReturn['eval']['tl_class'] = 'w50 wizard';
                if (isset($arrReturn['eval']['rgxp']) && in_array($arrReturn['eval']['rgxp'], ['date', 'time', 'datim'])) {
                    $arrReturn['eval']['dateFormat'] = Date::getFormatFromRgxp($arrReturn['eval']['rgxp']);
                }
                $arrReturn['eval']['datepicker'] = true;
                break;
            case 'color':
                $arrReturn['search'] = true;
                $arrReturn['inputType'] = 'text';
                $arrReturn['eval']['colorpicker'] = true;
                break;
            case 'select':
                $arrReturn['filter'] = true;
                $arrReturn['inputType'] = 'select';
                $arrReturn['eval']['chosen'] = true;
                $arrReturn['eval']['submitOnChange'] = (bool)$arrField['submitOnChange'];
                break;
            case 'radio':
                $arrReturn['filter'] = true;
                $arrReturn['inputType'] = 'radio';
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['submitOnChange'] = (bool)$arrField['submitOnChange'];
                break;
            case 'checkboxWizard':
                $arrReturn['filter'] = true;
                $arrReturn['inputType'] = 'checkboxWizard';
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['multiple'] = true;
                break;
            case 'checkbox':
                $arrReturn['filter'] = true;
                $arrReturn['inputType'] = 'checkbox';
                $arrReturn['eval']['tl_class'] = 'clr';
                if (!$blnMultiple) {
                    $arrReturn['eval']['tl_class'] = 'w50 m12';
                    unset($arrReturn['options_callback']);
                }
                break;
            case 'textarea':
                $arrReturn['search'] = true;
                $arrReturn['inputType'] = 'textarea';
                $arrReturn['eval']['tl_class'] = 'clr';
                if (isset($arrField['rte']) && $arrField['rte']) {
                    $arrReturn['eval']['rte'] = $arrField['rteType'] ?: 'tinyMCE';
                }
                break;
            case 'empty':
                $arrEmpty = [
                    'label' => $arrReturn['label'],
                    'eval' => [
                        'role' => $arrReturn['eval']['role']
                    ],
                    'sql' => $arrReturn['sql']
                ];
                $arrReturn = $arrEmpty;
                break;
            case 'pagepicker':
                $arrReturn['filter'] = true;
                $arrReturn['inputType'] = 'pageTree';
                $arrReturn['foreignKey'] = 'tl_page.title';
                $arrReturn['eval']['fieldType'] = $blnMultiple ? 'checkbox' : 'radio';
                if ($blnMultiple) {
                    $arrReturn['eval']['isSortable'] = true;
                    $arrReturn['eval']['csv'] = ',';
                }
                $arrReturn['relation'] = [
                    'type' => $blnMultiple ? 'hasMany' : 'hasOne',
                    'load' => 'lazy'
                ];
                if (isset($arrReturn['eval']['rgxp']) && $arrReturn['eval']['rgxp'] == 'url') {
                    $arrReturn['inputType'] = 'text';
                    $arrReturn['eval']['dcaPicker'] = true;
                    unset($arrReturn['relation']);
                    unset($arrReturn['foreignKey']);
                    unset($arrReturn['eval']['fieldType']);
                } else {
                    $arrReturn['eval']['tl_class'] = 'clr';
                }
                break;
            case 'customOptionWizard':
                $arrReturn['inputType'] = 'customOptionWizard';
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['multiple'] = true;
                $arrReturn['filter'] = true;
                $arrReturn['eval']['csv'] = ',';
                $arrReturn['eval']['addButtonLabel1'] = 'Tag hinzufügen';
                $arrReturn['eval']['addButtonLabel2'] = 'Hinzufügen';
                $arrReturn['options_callback'] = function ($objDataContainer = null) use ($arrField) {
                    $objOptions = Options::getInstance($arrField['fieldname'] . '.' . $arrField['pid']);
                    $objOptions::setParameter($arrField, $objDataContainer);
                    return $objOptions::getOptions();
                };
                if (isset($arrReturn['eval']['size'])) {
                    unset($arrReturn['eval']['size']);
                }
                break;
            case 'upload':
                $arrReturn['inputType'] = 'fileTree';
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['filesOnly'] = true;
                $arrReturn['eval']['fieldType'] = 'radio';
                $arrReturn['eval']['storeFile'] = '1';
                $arrReturn['eval']['extensions'] = $arrField['extensions'];
                $arrReturn['eval']['useHomeDir'] = $arrField['useHomeDir'];
                $arrReturn['eval']['imageWidth'] = $arrField['imageWidth'];
                $arrReturn['eval']['imageHeight'] = $arrField['imageHeight'];
                $arrReturn['eval']['doNotOverwrite'] = $arrField['doNotOverwrite'];

                if (($arrField['uploadFolder']??'')) {
                    $arrReturn['eval']['uploadFolder'] = StringUtil::binToUuid($arrField['uploadFolder']);
                }

                if (isset($arrReturn['eval']['role']) && $arrReturn['eval']['role']) {

                    $objRoleResolver = RoleResolver::getInstance(null);

                    switch (($objRoleResolver->getRole($arrReturn['eval']['role'])['type'] ?? '')) {
                        case 'image':
                            $arrReturn['eval']['isImage'] = true;
                            if ($arrField['imageSize']) {
                                $arrReturn['eval']['imageSize'] = $arrField['imageSize'];
                            }
                            break;
                        case 'gallery':
                            $arrReturn['eval']['files'] = true;
                            $arrReturn['eval']['filesOnly'] = false;
                            $arrReturn['eval']['isGallery'] = true;
                            $arrReturn['eval']['tl_class'] = 'clr';
                            $arrReturn['eval']['fieldType'] = 'checkbox';
                            $arrReturn['eval']['multiple'] = true;
                            if ($arrField['imageSize']) {
                                $arrReturn['eval']['imageSize'] = $arrField['imageSize'];
                            }
                            if (!empty($arrCatalog)) {
                                $arrReturn['eval']['orderField'] = Database::getInstance()->prepare('SELECT * FROM tl_catalog_field WHERE pid=? AND role=?')->limit(1)->execute($arrCatalog['id'], 'orderSRC')->fieldname;
                            }
                            break;
                        case 'files':
                        case 'file':
                            $arrReturn['eval']['files'] = true;
                            $arrReturn['eval']['filesOnly'] = true;
                            $arrReturn['eval']['isFile'] = true;
                            if ($arrReturn['eval']['role'] === 'files') {
                                $arrReturn['eval']['fieldType'] = 'checkbox';
                                $arrReturn['eval']['multiple'] = true;
                            }
                            break;
                    }
                }
                break;
        }

        if (isset($GLOBALS['CM_ROLES'][($arrField['role']??'')]) && isset($GLOBALS['CM_ROLES'][$arrField['role']]['eval'])) {
            foreach ($GLOBALS['CM_ROLES'][$arrField['role']]['eval'] as $strKey => $strOption) {
                $arrReturn['eval'][$strKey] = $strOption;
            }
        }

        if (isset($GLOBALS['TL_HOOKS']['parseCatalogField']) && is_array($GLOBALS['TL_HOOKS']['parseCatalogField'])) {
            foreach ($GLOBALS['TL_HOOKS']['parseCatalogField'] as $arrCallback) {
                $arrReturn = System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrReturn, $arrField, $this);
            }
        }

        Cache::set($strIdentifier, $arrReturn);

        return $arrReturn;
    }
}