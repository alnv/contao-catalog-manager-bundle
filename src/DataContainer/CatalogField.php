<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;


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
            'country',
            'firstname',
            'lastname',
            'email',
            'phone',
            'mobile',
            'avatar',
            'company'
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
        $strSql = Toolkit::getSql( $strType, $objDataContainer->activeRecord->row() );
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

            return $strType;
        }

        if ( $strType == $objDataContainer->activeRecord->type ) {

            return $strType;
        }

        $objCatalog = CatalogModel::findByPk( $objDataContainer->activeRecord->pid );

        if ( $objCatalog == null ) {

            return $strType;
        }

        $strSql = Toolkit::getSql( $strType, $objDataContainer->activeRecord->row() );
        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->changeFieldType( $objDataContainer->activeRecord->fieldname, $objCatalog->table, $strSql );

        return $strType;
    }
}