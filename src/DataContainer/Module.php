<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;


class Module {


    public function getTables() {

        $objDatabase = \Database::getInstance();

        return $objDatabase->listTables();
    }


    public function getFields( $dc = null ) {

        $arrReturn = [];

        if ( $dc == null ) {

            return $arrReturn;
        }

        if ( $dc->activeRecord == null ) {

            return $arrReturn;
        }

        $objDatabase = \Database::getInstance();
        $arrFields = $objDatabase->listFields( $dc->activeRecord->cmTable );

        foreach ( $arrFields as $arrField ) {

            $arrReturn[] = $arrField['name'];
        }

        return $arrReturn;
    }


    public function getListTemplates( \DataContainer $dc ) {

        if ( $dc == null ) {

            return [];
        }

        if ( !$dc->activeRecord->type ) {

            return [];
        }

        return \Controller::getTemplateGroup( 'cm_' . $dc->activeRecord->type . '_' );
    }
}