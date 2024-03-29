<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

class Module {

    public function getTables() {

        return (new \Alnv\ContaoCatalogManagerBundle\DataContainer\Catalog())->getTables();
    }

    public function getFields($dc = null) {

        $arrReturn = [];

        if ( $dc === null ) {
            return $arrReturn;
        }

        if ( $dc->activeRecord === null || !$dc->activeRecord->cmTable ) {
            return $arrReturn;
        }

        \System::loadLanguageFile($dc->activeRecord->cmTable, \Input::post('language'));
        \Controller::loadDataContainer($dc->activeRecord->cmTable);

        foreach ($GLOBALS['TL_DCA'][$dc->activeRecord->cmTable]['fields'] as $strField => $arrField) {

            $strValue = is_array($arrField['label']) ? $arrField['label'][0] : $strField;
            $arrReturn[$strField] = \StringUtil::decodeEntities($strValue);
        }
        return $arrReturn;
    }

    public function getListTemplates(\DataContainer $dc) {

        if ($dc == null) {
            return [];
        }

        if (!$dc->activeRecord->type) {
            return [];
        }

        switch ($dc->activeRecord->type) {
            case 'listing-map':
                $strType = 'map_view';
                break;
            default:
                $strType = $dc->activeRecord->type;
                break;
        }

        return \Controller::getTemplateGroup('cm_' . $strType . '_');
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
}