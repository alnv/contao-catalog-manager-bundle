<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;


abstract class CatalogWizard {


    protected function parseCatalog( $arrCatalog ) {

        $arrCatalog['columns'] = \StringUtil::deserialize( $arrCatalog['columns'], true );

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

        switch ( $arrField['type'] ) {

            case 'text':

                $arrReturn['inputType'] = 'text';

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

        return $arrReturn;
    }
}