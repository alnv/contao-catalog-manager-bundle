<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogFieldModel;

class CatalogOption {

    public function generatePidEntities() {

        $objDatabase = \Database::getInstance();
        $arrFields = $this->getFields();
        $intSorting = 128;

        foreach ( $arrFields as $arrField ) {
            $objEntity = $objDatabase->prepare('SELECT * FROM tl_catalog_option WHERE id = ?')->limit(1)->execute( $arrField['id'] );

            if ( $objEntity->numRows ) {
                if ( !$objEntity->pid ) {
                    $objDatabase->prepare('UPDATE tl_catalog_option %s WHERE id = ?')->set([
                        'tstamp' => time(),
                        'label' => $arrField['name'] . ' (' . $arrField['catalog']['name'] . ')'
                    ])->execute( $arrField['id'] );
                    continue;
                } else {
                    $objDatabase->prepare('INSERT INTO tl_catalog_option %s')->set([
                        'pid' => $objEntity->pid,
                        'value' => $objEntity->value,
                        'tstamp' => time(),
                        'sorting' => $objEntity->sorting,
                        'label' => $objEntity->label
                    ])->execute();
                }
            }
            $strLabel = $arrField['name'] . ' [' . $arrField['catalog']['name'] . ']';
            $objDatabase->prepare('INSERT INTO tl_catalog_option %s')->set([
                'pid' => 0,
                'tstamp' => time(),
                'label' => $strLabel,
                'id' => $arrField['id'],
                'sorting' => $intSorting,
                'value' => \Alnv\ContaoCatalogManagerBundle\Helper\Toolkit::generateAlias($strLabel, 'value', 'tl_catalog_option', $arrField['id'], 0)
            ])->execute();
            $intSorting += 128;
        }
    }

    protected function getFields() {

        $arrReturn = [];
        $objFields = CatalogFieldModel::findAll([
            'column' => [ 'tl_catalog_field.optionsSource=?', 'tl_catalog_field.published=?' ],
            'value' => [ 'options', '1' ],
            'order' => 'tl_catalog_field.sorting ASC',
            'eager' => true
        ]);

        if ( $objFields === null ) {
            return $arrReturn;
        }

        while ( $objFields->next() ) {

            $arrField = $objFields->row();
            $arrField['catalog'] = $objFields->getRelated('pid')->row();
            $arrReturn[] = $arrField;
        }

        return $arrReturn;
    }
}