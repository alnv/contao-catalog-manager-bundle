<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogOptionModel;

class Options {

    protected static $arrField = [];
    protected static $arrInstances = [];
    protected static $arrDataContainer = null;

    public static function getInstance( $strInstanceId ) {

        if ( !array_key_exists( $strInstanceId, self::$arrInstances ) ) {

            self::$arrInstances[ $strInstanceId ] = new self;
        }

        return self::$arrInstances[ $strInstanceId ];
    }

    public static function getOptions() {

        $arrReturn = [];
        switch ( static::$arrField['optionsSource'] ) {

            case 'options':
                $objOptions = CatalogOptionModel::findAll([
                    'column' => [ 'pid=?' ],
                    'value' => [ static::$arrField['id'] ],
                    'order' => 'sorting ASC'
                ]);
                if ( $objOptions === null ) {
                    return $arrReturn;
                }
                while ( $objOptions->next() ) {
                    $arrReturn[$objOptions->value] = self::getLabel($objOptions->value, $objOptions->label);;
                }
                break;

            case 'dbOptions':
                $objModel = new ModelWizard( static::$arrField['dbTable'] );
                $objModel = $objModel->getModel();
                $objEntities = $objModel->findAll([]);
                if ( $objEntities === null ) {
                    return $arrReturn;
                }
                while ( $objEntities->next() ) {
                    $strKey = $objEntities->{static::$arrField['dbKey']};
                    $strLabel = $objEntities->{static::$arrField['dbLabel']};
                    $arrReturn[$strKey] = self::getLabel($strKey, $strLabel);
                }
                return $arrReturn;
                break;

            default:
                return $arrReturn;
                break;
        }

        return $arrReturn;
    }

    public static function setParameter( $arrField, $objDataContainer = null ) {

        static::$arrField = $arrField;
        static::$arrDataContainer = $objDataContainer;
    }

    protected static function getLabel($strValue, $strFallbackLabel='') {

        return \Controller::replaceInsertTags(\Alnv\ContaoTranslationManagerBundle\Library\Translation::getInstance()->translate( static::$arrField['dbTable'] . '.option.' . $strValue , $strFallbackLabel));
    }
}