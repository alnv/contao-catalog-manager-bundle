<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Alnv\ContaoCatalogManagerBundle\Helper\CatalogWizard;
use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;


class Catalog extends CatalogWizard {


    protected $arrFields = [];
    protected $arrCatalog = [];
    protected $strIdentifier = null;


    public function __construct( $strIdentifier ) {

        $this->strIdentifier = $strIdentifier;
        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ( $objCatalog === null ) {

            return null;
        }

        $this->setDefaultFields();
        $this->setCustomFields();
        $this->arrCatalog = $this->parseCatalog( $objCatalog->row() );
        $objFields = CatalogFieldModel::findAll([
            'column' => [ 'pid=?', 'published=?' ],
            'value' => [ $this->arrCatalog['id'], '1' ],
            'order' => 'sorting ASC'
        ]);

        if ( $objFields === null ) {

            return null;
        }

        while ( $objFields->next() ) {

            $arrField = $this->parseField( $objFields->row() );

            if ( $arrField === null ) {

                continue;
            }

            $this->arrFields[ $objFields->fieldname ] = $arrField;
        }
    }


    public function getCatalog() {

        return $this->arrCatalog;
    }


    public function getFields() {

        return $this->arrFields;
    }


    public function getNaturalFields( $blnLabelOnly = true ) {

        $arrReturn = [];

        foreach ( $this->arrFields as $strFieldname => $arrField ) {

            $arrReturn[ $strFieldname ] = $strFieldname;
        }

        return $arrReturn;
    }


    protected function setDefaultFields() {

        array_insert( $this->arrFields, 0, [
            'id' => [
                'label' => [
                    Translation::getInstance()->translate( 'id', '' ),
                    Translation::getInstance()->translate( 'id.description', '' )
                ],
                'sql' => "int(10) unsigned NOT NULL auto_increment"
            ],
            'pid' => [
                'label' => [
                    Translation::getInstance()->translate( 'pid', '' ),
                    Translation::getInstance()->translate( 'pid.description', '' )
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'sorting' => [
                'label' => [
                    Translation::getInstance()->translate( 'sorting', '' ),
                    Translation::getInstance()->translate( 'sorting.description', '' )
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'tstamp' => [
                'label' => [
                    Translation::getInstance()->translate( 'tstamp', '' ),
                    Translation::getInstance()->translate( 'tstamp.description', '' )
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'invisible' => [
                'label' => [
                    Translation::getInstance()->translate( 'invisible', '' ),
                    Translation::getInstance()->translate( 'invisible.description', '' )
                ],
                'sql' => "char(1) NOT NULL default ''"
            ],
            'start' => [
                'label' => [
                    Translation::getInstance()->translate( 'start', '' ),
                    Translation::getInstance()->translate( 'start.description', '' )
                ],
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'stop' => [
                'label' => [
                    Translation::getInstance()->translate( 'stop', '' ),
                    Translation::getInstance()->translate( 'stop.description', '' )
                ],
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'alias' => [
                'label' => [
                    Translation::getInstance()->translate( 'alias', 'Alias' ),
                    Translation::getInstance()->translate( 'alias.description', '' )
                ],
                'eval' => [
                    'doNotCopy' => true
                ],
                'search' => true,
                'sql' => "varchar(128) NOT NULL default ''"
            ]
        ]);
    }


    protected function setCustomFields() {

        if ( !is_array( $GLOBALS['CM_CUSTOM_FIELDS'] ) || empty( $GLOBALS['CM_CUSTOM_FIELDS'] ) ) {

            return null;
        }

        $arrFields = [];

        foreach ( $GLOBALS['CM_CUSTOM_FIELDS'] as $strFieldname => $arrField ) {

            if ( isset( $arrField['table'] ) && $this->arrCatalog['table'] != $arrField['table'] ) {

                continue;
            }

            unset( $arrField['index'] );

            $arrFields[ $strFieldname ] = $arrField;
        }

        array_insert( $this->arrFields, 0, $arrFields );
    }
}