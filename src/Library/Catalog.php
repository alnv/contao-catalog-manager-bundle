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

        if ( $strIdentifier === null ) {

            return null;
        }

        $this->strIdentifier = $strIdentifier;
        $objCatalog = CatalogModel::findByTableOrModule( $this->strIdentifier );

        if ( $objCatalog === null ) {

            return null;
        }

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

        $this->setDefaultFields();
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

            $arrReturn[ $strFieldname ] = $blnLabelOnly ? $arrField['label'][0] : $strFieldname;
        }

        return $arrReturn;
    }

    protected function setDefaultFields() {

        \System::loadLanguageFile('default');

        array_insert( $this->arrFields, count( $this->arrFields ), [
            'id' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.id', $GLOBALS['TL_LANG']['MSC']['id'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.id', $GLOBALS['TL_LANG']['MSC']['id'][1] )
                ],
                'sql' => "int(10) unsigned NOT NULL auto_increment"
            ],
            'pid' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.pid', $GLOBALS['TL_LANG']['MSC']['pid'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.pid', $GLOBALS['TL_LANG']['MSC']['pid'][1] )
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'sorting' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.sorting', $GLOBALS['TL_LANG']['MSC']['sorting'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.sorting', $GLOBALS['TL_LANG']['MSC']['sorting'][1] )
                ],
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'tstamp' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.tstamp', $GLOBALS['TL_LANG']['MSC']['tstamp'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.tstamp', $GLOBALS['TL_LANG']['MSC']['tstamp'][1] )
                ],
                'flag' => 6,
                'sql' => "int(10) unsigned NOT NULL default '0'"
            ],
            'published' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.published', $GLOBALS['TL_LANG']['MSC']['published'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.published', $GLOBALS['TL_LANG']['MSC']['published'][1] )
                ],
                'inputType' => 'checkbox',
                'eval' => [
                    'multiple' => false,
                    'doNotCopy' => true,
                    'tl_class' => 'clr'
                ],
                'filter' => true,
                'exclude' => true,
                'sql' => "char(1) NOT NULL default ''"
            ],
            'start' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.start', $GLOBALS['TL_LANG']['MSC']['start'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.start', $GLOBALS['TL_LANG']['MSC']['start'][1] )
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp'=>'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'exclude' => true,
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'stop' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.stop', $GLOBALS['TL_LANG']['MSC']['stop'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.stop', $GLOBALS['TL_LANG']['MSC']['stop'][1] )
                ],
                'inputType' => 'text',
                'eval' => [
                    'rgxp'=>'datim',
                    'datepicker' => true,
                    'tl_class' => 'w50 wizard'
                ],
                'flag' => 6,
                'exclude' => true,
                'sql' => "varchar(10) NOT NULL default ''"
            ],
            'alias' => [
                'label' => [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.alias', $GLOBALS['TL_LANG']['MSC']['alias'][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.alias', $GLOBALS['TL_LANG']['MSC']['alias'][1] )
                ],
                'eval' => [
                    'doNotCopy' => true,
                    'role' => 'alias'
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

            if ( !isset( $arrField['label'] ) ) {

                $arrField['label'] = [
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.title.' . $strFieldname, $GLOBALS['TL_LANG']['MSC'][$strFieldname][0] ),
                    Translation::getInstance()->translate( $this->arrCatalog['table'] . '.field.description.' . $strFieldname, $GLOBALS['TL_LANG']['MSC'][$strFieldname][1] )
                ];
            }

            $arrFields[ $strFieldname ] = $arrField;
        }

        array_insert( $this->arrFields, 0, $arrFields );
    }
}