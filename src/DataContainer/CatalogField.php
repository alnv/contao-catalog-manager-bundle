<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;


class CatalogField {


    public function listFields( $arrRow ) {

        return $arrRow['name'];
    }


    public function getFieldTypes() {

        return [ 'text', 'color', 'date', 'textarea', 'select', 'radio', 'checkbox', 'upload' ];
    }


    public function getRoles( \DataContainer $dc ) {

        $arrRoles = array_keys( $GLOBALS['CM_ROLES'] );

        if ( !$dc->activeRecord->type ) {

            return $arrRoles;
        }

        switch ( $dc->activeRecord->type ) {

            case 'date':

                $arrDateRoles = [];

                foreach ( $GLOBALS['CM_ROLES'] as $strRole => $arrRole ) {

                    if ( $arrRole['group'] == 'date' ) {

                        $arrDateRoles[] = $strRole;
                    }
                }

                return $arrDateRoles;

                break;
        }

        return $arrRoles;
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


    public function changeFieldType( $strValue, \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->type || !$objDataContainer->activeRecord->fieldname ) {

            return $strValue;
        }

        $objCatalog = CatalogModel::findByPk( $objDataContainer->activeRecord->pid );

        if ( $objCatalog == null ) {

            return $strValue;
        }

        $strSql = Toolkit::getSql( $objDataContainer->activeRecord->type, $objDataContainer->activeRecord->row() );
        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->changeFieldType( $objDataContainer->activeRecord->fieldname, $objCatalog->table, $strSql );

        return $strValue;
    }


    public function getImageSizes() {

        $arrReturn = [];
        $objDatabase = \Database::getInstance();
        $objImagesSize = $objDatabase->prepare('SELECT * FROM tl_image_size')->execute();

        if ( !$objImagesSize->numRows ) {

            return $arrReturn;
        }

        while ( $objImagesSize->next() ) {

            $arrReturn[ $objImagesSize->id ] = $objImagesSize->name;
        }

        return $arrReturn;
    }
}