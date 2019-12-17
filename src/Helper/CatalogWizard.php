<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;


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

        $objParent = CatalogModel::findByPk( $strPid );

        if ( $objParent === null ) {

            return '';
        }

        return $objParent->table;
    }


    protected function parseField( $arrField ) {

        $strIdentifier = 'catalog_field_' . $arrField['id'];

        if ( \Cache::has( $strIdentifier ) ) {

            return \Cache::get( $strIdentifier );
        }

        if ( !$arrField['type'] ) {

            return null;
        }

        $blnMultiple = $arrField['multiple'] ? true : false;

        $arrReturn = [
            'exclude' => true,
            'filter' => $blnMultiple,
            'search' => !$blnMultiple,
            'sorting' => !$blnMultiple,
            'name' => $arrField['name'],
            'eval' => [
                'tl_class' => 'w50',
                'allowHtml' => true,
                'decodeEntities' => true,
                'multiple' => $blnMultiple,
                'role' => $arrField['role'] ?: '',
                'useAsAlias' => $arrField['useAsAlias'] ?: '',
                'mandatory' => $arrField['mandatory'] ? true : false
            ],
            'sql' => Toolkit::getSql( $arrField['type'], $arrField )
        ];

        if ( $arrField['includeBlankOption'] ) {

            $arrReturn['eval']['includeBlankOption'] = true;
            $arrReturn['eval']['blankOptionLabel'] = $arrField['blankOptionLabel'];
        }

        if ( in_array( $arrField['type'], [ 'select', 'radio', 'checkbox' ] ) ) {

            $arrReturn['options_callback'] = function ( $objDataContainer = null ) use ( $arrField ) {

                $objOptions = Options::getInstance( $arrField['fieldname'] . '.' . $arrField['pid'] );
                $objOptions::setParameter( $arrField, $objDataContainer );

                return $objOptions::getOptions();
            };
        }

        switch ( $arrField['type'] ) {

            case 'text':

                $arrReturn['inputType'] = 'text';

                break;

            case 'date':

                $arrReturn['default'] = time();
                $arrReturn['inputType'] = 'text';

                if ( $arrReturn['eval']['role'] ) {

                    $objRoleResolver = RoleResolver::getInstance( $this->arrCatalog['table'] );
                    $strRgxp = $objRoleResolver->getRole($arrReturn['eval']['role'])['type'];

                    if ( in_array( $strRgxp, [ 'date', 'time', 'datim' ] ) ) {

                        $arrReturn['eval']['rgxp'] = $strRgxp;
                    }
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

                if ( !$blnMultiple ) {

                    unset( $arrReturn['options_callback'] );
                }

                break;

            case 'textarea':

                $arrReturn['inputType'] = 'textarea';
                $arrReturn['eval']['tl_class'] = 'clr';

                if ( $arrField['rte'] ) {

                    $arrReturn['eval']['rte'] = 'tinyMCE';
                }

                break;

            case 'upload':

                $arrReturn['inputType'] = 'fileTree';
                $arrReturn['eval']['tl_class'] = 'clr';
                $arrReturn['eval']['filesOnly'] = true;
                $arrReturn['eval']['fieldType'] = 'radio';

                if ( $blnMultiple ) {

                    $arrReturn['eval']['fieldType'] = 'checkbox';
                }

                if ( $arrReturn['eval']['role'] ) {

                    $objRoleResolver = RoleResolver::getInstance( $this->arrCatalog['table'] );

                    switch ( $objRoleResolver->getRole($arrReturn['eval']['role'])['type'] ) {

                        case 'image':

                            $arrReturn['eval']['isImage'] = '1';

                            if ( $arrField['imageSize'] ) {

                                $arrReturn['eval']['imageSize'] = $arrField['imageSize'];
                            }

                            break;

                        case 'file':

                            $arrReturn['eval']['isFile'] = '1';

                            break;
                    }
                }

                break;
        }

        if ( isset( $GLOBALS['TL_HOOKS']['parseCatalogField'] ) && is_array( $GLOBALS['TL_HOOKS']['parseCatalogField'] ) ) {

            foreach ( $GLOBALS['TL_HOOKS']['parseCatalogField'] as $arrCallback ) {

                $this->import( $arrCallback[0] );
                $arrReturn = $this->{$arrCallback[0]}->{$arrCallback[1]}( $arrReturn, $arrField, $this );
            }
        }

        \Cache::set( $strIdentifier, $arrReturn );

        return $arrReturn;
    }
}