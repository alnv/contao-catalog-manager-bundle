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

    public function getOrderByStatements() {

        return [
            'ASC',
            'DESC'
        ];
    }

    public function getOperators() {

        return array_keys( $GLOBALS['CM_OPERATORS'] );
    }

    public function getFormIdentifier(\DataContainer $dc) {

        $arrReturn = [];
        if (!$dc->activeRecord->cmSource) {
            return $arrReturn;
        }

        switch ($dc->activeRecord->cmSource) {
            case 'dc':
                return $this->getTables();
            case 'form':
                $objForms = \FormModel::findAll();
                if ($objForms === null) {
                    while ($objForms->next()) {
                        $arrReturn[$objForms->id] = $objForms->title;
                    }
                }
                return $arrReturn;
        }
        return $arrReturn;
    }
}