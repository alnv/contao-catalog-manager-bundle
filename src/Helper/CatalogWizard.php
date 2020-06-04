<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;

abstract class CatalogWizard extends \System {

    protected $arrCache = [];

    protected function parseCatalog( $arrCatalog ) {

        $strIdentifier = 'catalog_' . $arrCatalog['table'];
        if ( \Cache::has( $strIdentifier ) ) {
            return \Cache::get( $strIdentifier );
        }

        $arrRelated = [];
        $arrChildren = [];
        $this->getRelatedTablesByCatalog( $arrCatalog, $arrRelated, $arrChildren );
        $arrCatalog['columns'] = \StringUtil::deserialize( $arrCatalog['columns'], true );
        $arrCatalog['headerFields'] = \StringUtil::deserialize( $arrCatalog['headerFields'], true );
        $arrCatalog['order'] = \Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit::decodeJson( $arrCatalog['order'], [
            'option' => 'field',
            'option2' => 'order'
        ]);
        $arrCatalog['ptable'] = '';
        $arrCatalog['related'] = $arrRelated;
        $arrCatalog['ctable'] = $arrChildren;
        $arrCatalog['_table'] = $arrCatalog['table'];

        if ( $arrCatalog['pid'] ) {
            $arrCatalog['ptable'] = $this->getParentCatalogByPid( $arrCatalog['pid'] );
        }

        if ( $arrCatalog['enableContentElements'] ) {
            $arrCatalog['ctable'][] = 'tl_content';
            $arrCatalog['related'][] = 'tl_content';
        }

        if ( isset( $GLOBALS['TL_HOOKS']['parseCatalog'] ) && is_array( $GLOBALS['TL_HOOKS']['parseCatalog'] ) ) {
            foreach ( $GLOBALS['TL_HOOKS']['parseCatalog'] as $arrCallback ) {
                $this->import( $arrCallback[0] );
                $arrCatalog = $this->{$arrCallback[0]}->{$arrCallback[1]}( $arrCatalog, $this );
            }
        }

        \Cache::set( $strIdentifier, $arrCatalog );

        return $arrCatalog;
    }

    protected function getRelatedTablesByCatalog( $arrCatalog, &$arrRelated, &$arrChildren, $intLevel = 0 ) {

        $objChildCatalogs = CatalogModel::findChildrenCatalogsById( $arrCatalog['id'] );

        if ( $objChildCatalogs === null ) {

            return null;
        }

        while ( $objChildCatalogs->next() ) {

            if ( $objChildCatalogs->table ) {
                $arrRelated[] = $objChildCatalogs->table;
            }

            if ( $objChildCatalogs->enableContentElements && !in_array( 'tl_content', $arrRelated ) ) {
                $arrRelated[] = 'tl_content';
            }

            if ( !$intLevel ) {
                $arrChildren[] = $objChildCatalogs->table;
            }

            $intLevel++;

            self::getRelatedTablesByCatalog( $objChildCatalogs->row(), $arrRelated, $arrChildren, $intLevel );
        }
    }

    protected function getParentCatalogByPid( $strPid ) {

        $objParent = CatalogModel::findByPk($strPid);

        if ( $objParent === null ) {
            return '';
        }

        return $objParent->table;
    }

    public function parseField( $arrField, $arrCatalog = [] ) {

        $strIdentifier = 'catalog_field_' . $arrField['id'];

        if (\Cache::has( $strIdentifier)) {

            return \Cache::get($strIdentifier);
        }

        if (!$arrField['type']) {

            return null;
        }

        $blnMultiple = $arrField['multiple'] ? true : false;
        $arrField['description'] = trim(strip_tags($arrField['description']));
        $arrReturn = [
            'exclude' => true,
            'filter' => $blnMultiple,
            'search' => !$blnMultiple,
            'sorting' => !$blnMultiple,
            'name' => $arrField['name'],
            'label' => [
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.title.' . $arrField['fieldname'], $arrField['name']),
                Translation::getInstance()->translate($this->arrCatalog['table'] . '.field.description.' . $arrField['fieldname'], $arrField['description']),
            ],
            'eval' => [
                'tl_class' => 'w50',
                'allowHtml' => true,
                'decodeEntities' => true,
                'multiple' => $blnMultiple,
                'role' => $arrField['role'] ?: '',
                'useAsAlias' => $arrField['useAsAlias'] ?: '',
                'mandatory' => $arrField['mandatory'] ? true : false,
                'size' => $arrField['size'] ? intval($arrField['size']) : 1
            ],
            'sql' => Toolkit::getSql($arrField['type'], $arrField)
        ];

        if ($arrField['includeBlankOption']) {

            $arrReturn['eval']['includeBlankOption'] = true;
            $arrReturn['eval']['blankOptionLabel'] = $arrField['blankOptionLabel'];
        }

        if (in_array($arrField['type'], ['select', 'radio', 'checkbox'])) {

            $arrReturn['options_callback'] = function ($objDataContainer = null) use ($arrField) {
                $objOptions = Options::getInstance( $arrField['fieldname'] . '.' . $arrField['pid'] );
                $objOptions::setParameter( $arrField, $objDataContainer );
                return $objOptions::getOptions();
            };

            if ($arrField['optionsSource'] == 'dbOptions') {
                $arrReturn['relation'] = [
                    'load' => 'lazy',
                    'field' => $arrField['dbKey'],
                    'table' => $GLOBALS['TL_DCA'][$arrField['dbTable']]['config']['_table'] ?: $arrField['dbTable'],
                    'type' => $blnMultiple ? 'hasMany' : 'hasOne'
                ];
            }
        }

        if ($strRgxp = Toolkit::getRgxp($arrField['type'], $arrField)) {

            $arrReturn['eval']['rgxp'] = $strRgxp;
        }

        switch ($arrField['type']) {

            case 'text':
                $arrReturn['inputType'] = 'text';
                if ($arrReturn['eval']['multiple'] && $arrReturn['eval']['size'] > 1) {
                    $arrReturn['eval']['tl_class'] = 'long clr';
                }
                break;

            case 'date':
                $arrReturn['flag'] = 6;
                $arrReturn['inputType'] = 'text';
                $arrReturn['eval']['tl_class'] = 'w50 wizard';
                if ($arrReturn['eval']['rgxp'] && in_array($arrReturn['eval']['rgxp'], ['date', 'time', 'datim'])) {
                    $arrReturn['eval']['dateFormat'] = \Date::getFormatFromRgxp($arrReturn['eval']['rgxp']);
                }
                $arrReturn['eval']['datepicker'] = true;
                break;

            case 'color':
                $arrReturn['inputType'] = 'text';
                $arrReturn['eval']['colorpicker'] = true;
                break;

            case 'select':
                $arrReturn['inputType'] = 'select';
                $arrReturn['eval']['chosen'] = true;
                break;

            case 'radio':
                $arrReturn['inputType'] = 'radio';
                $arrReturn['eval']['tl_class'] = 'clr';
                break;

            case 'checkbox':
                $arrReturn['inputType'] = 'checkbox';
                $arrReturn['eval']['tl_class'] = 'clr';
                if (!$blnMultiple) {
                    $arrReturn['eval']['tl_class'] = 'w50 m12';
                    unset($arrReturn['options_callback']);
                }
                break;

            case 'textarea':
                $arrReturn['inputType'] = 'textarea';
                $arrReturn['eval']['tl_class'] = 'clr';
                if ( $arrField['rte'] ) {
                    $arrReturn['eval']['rte'] = 'tinyMCE';
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
                $arrReturn['eval']['tl_class'] = 'clr';
                break;

            case 'upload':
                $arrReturn['inputType'] = 'fileTree';
                $arrReturn['filter'] = false;
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['filesOnly'] = true;
                $arrReturn['eval']['fieldType'] = 'radio';
                $arrReturn['eval']['storeFile'] = '1';
                $arrReturn['eval']['extensions'] = $arrField['extensions'];
                $arrReturn['eval']['useHomeDir'] = $arrField['useHomeDir'];
                $arrReturn['eval']['imageWidth'] = $arrField['imageWidth'];
                $arrReturn['eval']['imageHeight'] = $arrField['imageHeight'];
                $arrReturn['eval']['doNotOverwrite'] = $arrField['doNotOverwrite'];
                $arrReturn['eval']['uploadFolder'] = \StringUtil::binToUuid( $arrField['uploadFolder'] );
                if ($arrReturn['eval']['role']) {
                    $objRoleResolver = RoleResolver::getInstance(null);
                    switch ($objRoleResolver->getRole($arrReturn['eval']['role'])['type']) {
                        case 'image':
                            $arrReturn['eval']['isImage'] = true;
                            if ( $arrField['imageSize'] ) {
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
                            if ( $arrField['imageSize'] ) {
                                $arrReturn['eval']['imageSize'] = $arrField['imageSize'];
                            }
                            if (!empty($arrCatalog)) {
                                $arrReturn['eval']['orderField'] = \Database::getInstance()->prepare('SELECT * FROM tl_catalog_field WHERE pid=? AND role=?')->limit(1)->execute($arrCatalog['id'],'orderSRC')->fieldname;
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

        if (isset($GLOBALS['TL_HOOKS']['parseCatalogField']) && is_array($GLOBALS['TL_HOOKS']['parseCatalogField'])) {
            foreach ( $GLOBALS['TL_HOOKS']['parseCatalogField'] as $arrCallback ) {
                $this->import($arrCallback[0]);
                $arrReturn = $this->{$arrCallback[0]}->{$arrCallback[1]}($arrReturn, $arrField, $this);
            }
        }

        \Cache::set( $strIdentifier, $arrReturn );

        return $arrReturn;
    }
}