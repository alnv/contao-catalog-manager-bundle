<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;


abstract class CatalogWizard {


    protected function parseCatalog( $arrCatalog ) {

        return $arrCatalog;
    }


    protected function parseField( $arrField ) {

        if ( !$arrField['type'] ) {

            return null;
        }

        $blnMultiple = $arrField['multiple'] ? true : false;

        $arrReturn = [
            'exclude' => true,
            'filter' => $blnMultiple,
            'search' => !$blnMultiple,
            'sorting' => !$blnMultiple,
            'eval' => [
                'allowHtml' => true,
                'decodeEntities' => true,
                'multiple' => $blnMultiple,
                'mandatory' => $arrField['mandatory'] ? true : false
            ],
            'sql' => Toolkit::getSql( $arrField['type'], $arrField )
        ];

        switch ( $arrField['type'] ) {

            case 'text':

                $arrReturn['inputType'] = 'text';

                break;

            case 'textarea':

                $arrReturn['inputType'] = 'textarea';

                break;
        }

        return $arrReturn;
    }


    protected function getMaxLengthBySql( $strSql ) {

        return '';
    }
}