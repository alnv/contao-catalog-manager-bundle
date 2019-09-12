<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;


class CatalogField {


    public function listFields( $arrRow ) {

        return $arrRow['name'];
    }


    public function getFieldTypes() {

        return [ 'text', 'textarea', 'select', 'radio', 'checkbox', 'upload' ];
    }


    public function getRoles() {

        return [
            'street',
            'street_number',
            'city',
            'postal',
            'country'
        ];
    }

    // @todo make it reuseabel
    public function toggleIcon( $arrRow, $strHref, $strLabel, $strTitle, $strIcon, $strAttributes ) {

        if ( \Input::get('tid') ) {

            $this->toggleVisibility( \Input::get('tid'), ( \Input::get('state') == 1 ), ( @func_get_arg(12) ?: null ) );
            \Controller::redirect( \Controller::getReferer() );
        }

        $strHref .= '&amp;tid='.$arrRow['id'].'&amp;state='.( $arrRow['published'] ? '' : 1);

        if ( !$arrRow['published'] ) {

            $strIcon = 'invisible.svg';
        }

        return '<a href="'. \Backend::addToUrl( $strHref ) . '" title="'. \StringUtil::specialchars( $strTitle ) .'"'. $strAttributes. '>'.\Image::getHtml( $strIcon, $strLabel, 'data-state="' . ( $arrRow['published'] ? 1 : 0 ) . '"' ).'</a> ';
    }


    protected function toggleVisibility() {

        // @todo
    }


    public function watchFieldname( $strFieldname, \DataContainer $objDataContainer ) {

        $objDatabase = \Database::getInstance();
        $strType = $objDataContainer->activeRecord->type;
        $strSql = $this->getSql( $strType, $objDataContainer->activeRecord->row() );
        $objCatalog = CatalogModel::findByPk( $objDataContainer->activeRecord->pid );
        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();

        if ( !$strFieldname || $objCatalog == null ) {

            return '';
        }

        $strTable = $objCatalog->table;

        if ( $strFieldname == $objDataContainer->activeRecord->fieldname && $objDatabase->fieldExists( $strFieldname, $strTable, true ) ) {

            return $strFieldname;
        }

        if ( $strFieldname != $objDataContainer->activeRecord->fieldname && $objDataContainer->activeRecord->fieldname ) {

            if ( !$objDatabaseBuilder->renameFieldname( $objDataContainer->activeRecord->fieldname, $strFieldname, $strTable, $strSql ) ) {

                throw new \Exception( sprintf( 'fieldname "%s" already exists in %s.', $strFieldname, $strTable ) );
            }
        }

        if ( !$objDatabaseBuilder->createFieldIfNotExist( $strFieldname, $strTable, $strSql ) && !$objDataContainer->activeRecord->fieldname ) {

            throw new \Exception( sprintf( 'fieldname "%s" already exists in %s.', $strFieldname, $strTable ) );
        }

        return $strFieldname;
    }


    public function changeFieldType( $strType, \DataContainer $objDataContainer ) {

        if ( !$strType || !$objDataContainer->activeRecord->fieldname ) {

            return '';
        }

        if ( $strType == $objDataContainer->activeRecord->type ) {

            return $strType;
        }

        $objCatalog = CatalogModel::findByPk( $objDataContainer->activeRecord->pid );

        if ( $objCatalog == null ) {

            return $strType;
        }

        $strSql = $this->getSql( $strType, $objDataContainer->activeRecord->row() );
        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->changeFieldType( $objDataContainer->activeRecord->fieldname, $objCatalog->table, $strSql );

        return $strType;
    }


    public function getSql( $strType, $arrOptions = [] ) {

        $arrSql = [

            'vc255' => "varchar(255) NOT NULL default '%s'",
            'c1' => "char(1) NOT NULL default ''",
            'i10' => "int(10) unsigned NOT NULL default '0'",
            'iNotNull10' => "int(10) unsigned NULL",
            'text' => "text NULL",
            'longtext' => "longtext NULL",
            'blob' => "blob NULL"
        ];

        // @todo numbers

        switch ( $strType ) {

            case 'text':

                if ( $arrOptions['multiple'] ) {

                    return $arrSql['blob'];
                }

                return sprintf( $arrSql['vc255'], ( $arrOptions['default'] ? $arrOptions : '' ) );

                break;

            case 'textarea':

                if ( $arrOptions['tinyMce'] ) {

                    return $arrSql['longtext'];
                }

                return $arrSql['text'];

                break;

            case 'select':

                if ( $arrOptions['multiple'] ) {

                    return $arrSql['blob'];
                }

                return $arrSql['vc255'];

                break;

            case 'checkbox':

                if ( !$arrOptions['multiple'] ) {

                    return $arrSql['c1'];
                }

                return $arrSql['blob'];

                break;

            case 'radio':

                return $arrSql['vc255'];

                break;

            case 'upload':

                return $arrSql['blob'];

                break;

            default:

                return $arrSql['blob'];

                break;
        }
    }
}