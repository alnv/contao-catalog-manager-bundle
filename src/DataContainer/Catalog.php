<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;

class Catalog {

    public function addIcon($arrRow, $strLabel, \DataContainer $objDataContainer=null, $strAttributes='', $blnReturnImage=false, $blnProtected=false) {

        $strIcon = 'bundles/alnvcontaoassetsmanager/icons/'. ($arrRow['pid'] ? 'sub' : '') .'module-icon.svg';
        $strAttributes .= 'class="resize-image"';
        return \Image::getHtml($strIcon, $strLabel, $strAttributes) . ' '. $strLabel .'<span style="color:#999;padding-left:3px">['. $arrRow['table'] .']</span>';
    }

    public function getCatalogTypes() {

        return array_keys( $GLOBALS['TL_LANG']['tl_catalog']['reference']['type'] );
    }

    public function getSortingTypes() {

        return array_keys($GLOBALS['TL_LANG']['tl_catalog']['reference']['sortingType']);
    }

    public function getCutOperationButton($arrRow, $href, $strLabel, $strTitle, $strIcon, $attributes){

        if (!$arrRow['table']) {
            return '';
        }

        $objEntities = \Database::getInstance()->prepare('SELECT * FROM ' . $arrRow['table'])->limit(1)->execute();

        if ($objEntities->numRows) {
            return '<a title="'. \StringUtil::specialchars($GLOBALS['TL_LANG']['tl_catalog']['cutEmptyHint']) .'">' . \Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $strIcon)) . '</a>';
        }

        return '<a href="' . \Backend::addToUrl($href . '&amp;id=' . $arrRow['id']) . '" title="' . \StringUtil::specialchars($strTitle) . '"' . $attributes . '>' . \Image::getHtml($strIcon, $strLabel) . '</a> ';
    }

    public function getDataContainers() {

        return $GLOBALS['CM_DATA_CONTAINERS'];
    }

    public function getModes( \DataContainer $objDataContainer ) {

        $arrModes = array_keys( $GLOBALS['TL_LANG']['tl_catalog']['reference']['mode'] );

        if ( !$objDataContainer->activeRecord->pid ) {

            if ( ( $intPos = array_search( 'parent', $arrModes ) ) !== false ) {

                unset( $arrModes[ $intPos ] );
            }
        }

        else {

            if ( ( $intPos = array_search( 'tree', $arrModes ) ) !== false ) {

                unset( $arrModes[ $intPos ] );
            }
        }

        return array_values( $arrModes );
    }

    public function getFlags() {

        return [ '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ];
    }

    public function getParentFields( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->pid ) {

            return [];
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( $objDataContainer->activeRecord->pid );

        return $objCatalog->getNaturalFields();
    }

    public function getFields( $objDataContainer = null ) {

        if ( $objDataContainer === null ) {

            return [];
        }

        if ( !$objDataContainer->activeRecord->table ) {

            return [];
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( $objDataContainer->activeRecord->table );

        return $objCatalog->getNaturalFields();
    }

    public function generateModulename( \DataContainer $objDataContainer ) {

        if ( $objDataContainer->activeRecord->type !== 'catalog' || !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabase = \Database::getInstance();
        $strModulename = 'module_' . strtolower( $objDataContainer->activeRecord->table );
        $objDatabase->prepare('UPDATE ' . $objDataContainer->table . ' %s WHERE id = ?')->set([ 'tstamp' => time(), 'module' => $strModulename ])->execute( $objDataContainer->id );
    }

    public function getNavigation() {

        $arrReturn = [];

        if ( !is_array( $GLOBALS['BE_MOD'] ) || empty( $GLOBALS['BE_MOD'] ) ) {

            return $arrReturn;
        }

        foreach ( $GLOBALS['BE_MOD'] as $strModulename => $arrModules ) {

            $strModuleLabel = $GLOBALS['TL_LANG']['MOD'][ $strModulename ] ?: $strModulename;

            $arrReturn[ $strModulename ] = $strModuleLabel;
        }

        return $arrReturn;
    }

    public function watchTable( $strTable, \DataContainer $objDataContainer ) {

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabase = \Database::getInstance();

        if ( !$strTable ) {

            return '';
        }

        if ( $strTable == $objDataContainer->activeRecord->table && $objDatabase->tableExists( $strTable, true ) ) {

            return $strTable;
        }

        if ( $strTable != $objDataContainer->activeRecord->table && $objDataContainer->activeRecord->table ) {

            if ( !$objDatabaseBuilder->renameTable( $objDataContainer->activeRecord->table, $strTable ) ) {

                throw new \Exception( sprintf( 'table "%s" already exists in catalog manager.', $strTable ) );
            }
        }

        if ( !$objDatabaseBuilder->createTableIfNotExist( $strTable ) ) {

            throw new \Exception( sprintf( 'table "%s" already exists in catalog manager.', $strTable ) );
        }

        return $strTable;
    }

    public function createCustomFields( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->createCustomFieldsIfNotExists( $objDataContainer->activeRecord->table );
    }

    public function deleteTable( \DataContainer $objDataContainer ) {

        if ( !$objDataContainer->activeRecord->table ) {

            return null;
        }

        $objDatabaseBuilder = new \Alnv\ContaoCatalogManagerBundle\Library\Database();
        $objDatabaseBuilder->deleteTable( $objDataContainer->activeRecord->table );
    }

    public function getOrderByStatements() {

        return [
            'ASC',
            'DESC'
        ];
    }

    public function toggleIcon( $arrRow, $strHref, $strLabel, $strTitle, $strIcon, $strAttributes ) {

        if ( \Input::get('tid') ) {

            $this->toggleVisibility( \Input::get('tid'), ( \Input::get('state') == 1 ), ( @func_get_arg(12) ?: null ) );

            \Controller::redirect( \Controller::getReferer() );
        }

        $strHref .= '&amp;tid='.$arrRow['id'].'&amp;state='.( $arrRow['published'] ? '' : 1);

        if ( !$arrRow['published'] ) {

            $strIcon = 'invisible.svg';
        }

        return '<a href="'. \Backend::addToUrl( $strHref ) . '" title="'. \StringUtil::specialchars( $strTitle ) .'"'. $strAttributes. '>'.\Image::getHtml( $strIcon, $strLabel, 'data-state="' . ( $arrRow['published'] ? 1 : 0 ) . '"' ).'</a> ';
    }

    protected function toggleVisibility( $intId, $blnVisible, \DataContainer $objDataContainer=null ) {

        \Input::setGet('id', $intId);
        \Input::setGet('act', 'toggle');

        $strTable = Toolkit::getTableByDo();

        if ( !$strTable ) {

            return null;
        }

        if ( $objDataContainer ) {

            $objDataContainer->id = $intId;
        }

        if (is_array($GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($objDataContainer);
                }
                elseif (is_callable($callback)) {
                    $callback($objDataContainer);
                }
            }
        }

        if ( $objDataContainer ) {

            $objRow = \Database::getInstance()->prepare('SELECT * FROM '. $strTable .' WHERE id=?')->limit( 1 )->execute( $intId );

            if ($objRow->numRows) {

                $objDataContainer->activeRecord = $objRow;
            }
        }

        $objVersions = new \Versions( $strTable, $intId );
        $objVersions->initialize();

        if (is_array($GLOBALS['TL_DCA'][$strTable]['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['fields']['published']['save_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($blnVisible, $objDataContainer);
                }
                elseif (is_callable($callback)) {
                    $callback($blnVisible, $objDataContainer);
                }
            }
        }

        $intTime = time();

        \Database::getInstance()->prepare('UPDATE '. $strTable .' %s WHERE id=?')->set([
            'tstamp' => time(),
            'published' => ($blnVisible ? '1' : '')
        ])->execute($intId);

        if ( $objDataContainer ) {

            $objDataContainer->activeRecord->tstamp = $intTime;
            $objDataContainer->activeRecord->published = ($blnVisible ? '1' : '');
        }

        if (is_array($GLOBALS['TL_DCA'][$strTable]['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA'][$strTable]['config']['onsubmit_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $this->{$callback[0]}->{$callback[1]}($objDataContainer);
                }
                elseif (is_callable($callback)) {
                    $callback($objDataContainer);
                }
            }
        }

        $objVersions->create();
    }

    public function getTables() {

        return \Database::getInstance()->listTables();
    }

    public function getDbFields(\DataContainer $dc) {

        $arrReturn = [];

        if ($dc === null) {

            return $arrReturn;
        }

        if ($dc->activeRecord === null || !$dc->activeRecord->dbTable) {

            return $arrReturn;
        }

        \System::loadLanguageFile($dc->activeRecord->dbTable);
        \Controller::loadDataContainer($dc->activeRecord->dbTable);

        foreach ( $GLOBALS['TL_DCA'][$dc->activeRecord->dbTable]['fields'] as $strField => $arrField ) {

            $arrReturn[$strField] = (is_array($arrField['label']) && isset($arrField['label'][0])) ? $arrField['label'][0] : $strField;
        }

        return $arrReturn;
    }

    public function getOperators() {

        return array_keys( $GLOBALS['CM_OPERATORS'] );
    }
}