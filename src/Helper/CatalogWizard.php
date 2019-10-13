<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\Options;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;


abstract class CatalogWizard {


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

        $arrCatalog['ptable'] = '';
        $arrCatalog['related'] = $arrRelated;
        $arrCatalog['ctable'] = $arrChildren;

        if ( $arrCatalog['pid'] ) {

            $arrCatalog['ptable'] = $this->getParentCatalogByPid( $arrCatalog['pid'] );
        }

        if ( $arrCatalog['enableContentElements'] ) {

            $arrCatalog['ctable'][] = 'tl_content';
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
                'mandatory' => $arrField['mandatory'] ? true : false
            ],
            'sql' => Toolkit::getSql( $arrField['type'], $arrField )
        ];

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

            case 'select':

                $arrReturn['inputType'] = 'select';
                $arrReturn['eval']['chosen'] = true;

                break;

            case 'textarea':

                $arrReturn['inputType'] = 'textarea';

                break;

            case 'upload':

                $arrReturn['inputType'] = 'fileTree';

                // @todo image or doc
                $arrReturn['eval']['fieldType'] = 'radio';
                $arrReturn['eval']['filesOnly'] = true;
                $arrReturn['eval']['isImage'] = '1';

                break;
        }

        \Cache::set( $strIdentifier, $arrReturn );

        return $arrReturn;
    }
}