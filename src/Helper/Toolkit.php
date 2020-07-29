<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;

class Toolkit {

    public static function getLabel($strItem) {

        return is_array($GLOBALS['TL_LANG']['MSC'][$strItem]) ? $GLOBALS['TL_LANG']['MSC'][$strItem][0] : $GLOBALS['TL_LANG']['MSC'][$strItem];
    }

    public static function parse( $varValue, $strDelimiter = ', ', $strField = 'label' ) {

        if ( is_array( $varValue ) ) {
            $arrValues = array_map( function ( $arrValue ) use ( $strField ) {
                return $arrValue[ $strField ];
            }, $varValue );
            return implode( $strDelimiter, $arrValues );
        }

        return $varValue;
    }

    public static function compress($strTemplate) {

        $strTemplate = str_replace(["\r\n", "\r"], "\n", $strTemplate);
        $arrLines = explode("\n", $strTemplate);
        $arrNewLines = [];
        foreach ($arrLines as $strLine) {
            if(!empty($strLine))
                $arrNewLines[] = trim($strLine);
        }
        return implode($arrNewLines);
    }

    public static function getFilterValue($strField) {
        return \Input::get($strField) ?: \Input::post($strField);
    }

    public static function getSqlTypes() {

        return [
            'vc255' => "varchar(255) NOT NULL default '%s'",
            'vc8' => "varchar(8) NOT NULL default '%s'",
            'c1' => "char(1) NOT NULL default ''",
            'i10' => "int(10) unsigned NOT NULL default '0'",
            'i10NullAble' => "int(10) unsigned NULL",
            'float' => "decimal(12,2) NOT NULL default '0.00'",
            'text' => "text NULL",
            'longtext' => "longtext NULL",
            'blob' => "blob NULL"
        ];
    }

    public static function getRgxp($strType, $arrOptions = []) {

        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(null);
        $arrRole = $objRoleResolver->getRole($arrOptions['role']);

        if (isset($arrRole['rgxp']) && $arrRole['rgxp']) {

            return $arrRole['rgxp'];
        }

        return '';
    }

    public static function getSql($strType, $arrOptions = []) {

        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(null);
        $arrRole = $objRoleResolver->getRole($arrOptions['role']);

        if (isset($arrRole['sql']) && $arrRole['sql']) {
            return sprintf($arrRole['sql'], ($arrOptions['default'] ? $arrOptions['default'] : ''));
        }

        $arrSql = static::getSqlTypes();

        if ($arrOptions['multiple']) {
            return $arrSql['blob'];
        }

        switch ($strType) {
            case 'color':
                return sprintf($arrSql['vc8'], ($arrOptions['default'] ? $arrOptions['default'] : ''));
            case 'date':
                return sprintf( $arrSql['i10NullAble'], ($arrOptions['default'] ? $arrOptions['default'] : '' ));
            default:
                return $arrSql['blob'];
        }
    }

    public static function parseDetailLink( $varPage, $strAlias ) {

        $arrPage = null;
        if ( is_numeric( $varPage ) && $varPage ) {
            $objPage = \PageModel::findByPk( $varPage );
            if ( $objPage !== null ) {
                $arrPage = $objPage->row();
            }
        }

        if ( is_array( $varPage ) && !empty( $varPage ) ) {
            $arrPage = $varPage;
        }

        return \Controller::generateFrontendUrl( $arrPage, $strAlias ? '/' . $strAlias : '' );
    }

    public static function parseImage($varImage) {
        if (!is_array($varImage) && ( \Validator::isBinaryUuid($varImage) || \Validator::isUuid($varImage) )) {
            $objFile = \FilesModel::findByUuid($varImage);
            if ($objFile !== null) {
                return $objFile->path;
            }
        }
        if (!is_array($varImage) && empty($varImage)) {
            return '';
        }
        if ( isset($varImage['img']) ) {
            return $varImage['img']['src'];
        }
        return $varImage[0]['img']['src'];
    }

    public static function parseParametersFromString( $strParameter ) {

        $arrChunks = explode('?', urldecode( $strParameter ), 2 );
        $strSource = \StringUtil::decodeEntities( $arrChunks[1] );
        $strSource = str_replace( '[&]', '&', $strSource );

        return explode( '&', $strSource );
    }

    public static function getValueFromUrl( $arrValue ) {

        if ( $arrValue === '' || $arrValue === null ) {
            return '';
        }

        if ( is_array( $arrValue ) ) {
            return serialize( $arrValue );
        }

        return $arrValue;
    }

    public static function getOrderByStatementFromArray( $arrOrder ) {

        return implode(',', array_filter( array_map( function ( $arrOrder ) {
            if ( !isset( $arrOrder['field'] ) || !$arrOrder['field'] ) {
                return '';
            }

            if ( !$arrOrder['order'] ) {
                $arrOrder['order'] = 'ASC';
            }

            return $arrOrder['field'] . ' ' . $arrOrder['order'];
            }, $arrOrder ) ) );
    }

    public static function renderRow($arrRow, $arrLabelFields, $arrCatalog, $arrFields) {

        $arrColumns = [];

        if (isset($arrCatalog['flagField']) && $arrCatalog['flagField']) {
            if (!in_array($arrCatalog['flagField'], $arrColumns)) {
                $arrLabelFields[] = $arrCatalog['sortingType'] !== 'none' ? $arrCatalog['flagField'] : '-';
            }
        }

        foreach ($arrLabelFields as $strField) {
            $arrColumns[$strField] = static::parseCatalogValue($arrRow[$strField], \Widget::getAttributesFromDca($arrFields[$strField], $strField, $arrRow[$strField], $strField, $arrCatalog['table']), $arrRow, true);
        }

        if (count($arrColumns) < 2) {
            return array_values($arrColumns)[0];
        }

        if ($arrCatalog['showColumns'] && $arrCatalog['mode'] == 'list') {
            return $arrColumns;
        }

        $intIndex = -1;
        $arrLabels = [];
        $strTemplate = '<div class="tl_content_left">';

        foreach ($arrColumns as $strField => $strValue) {
            if ($strValue === '' || $strValue === null) {
                continue;
            }
            if (in_array($strField, ['sorting'])) {
                continue;
            }
            $intIndex += 1;
            if (!$intIndex) {
                $strTemplate .= $strValue;
                continue;
            }
            $arrLabels[] = $strValue;
        }

        if (!empty($arrLabels)) {
            $strTemplate .= '<span style="color:#999;padding-left:3px">('. implode( $arrLabels, ' - ' ) .')</span>' . '</div>';
        }

        return $strTemplate;
    }

    public static function renderTreeRow( $arrRow, $strLabel, $arrLabelFields, $arrCatalog, $arrFields ) {

        $intIndex = 0;
        $arrColumns = [];
        $strTemplate = '';
        $strImage = 'articles';

        foreach ($arrLabelFields as $strField) {
            $arrColumns[$strField] = static::parseCatalogValue( $arrRow[ $strField ], \Widget::getAttributesFromDca( $arrFields[ $strField ], $strField, $arrRow[ $strField ], $strField, $arrCatalog['table'] ), $arrRow, true );
        }

        if (count($arrColumns) < 2) {
            return array_values($arrColumns)[0];
        }

        foreach ($arrColumns as $strField => $strValue) {
            $strTemplate .= !$intIndex ? $strValue :  ( ' <span class="'. $strField .'" style="color:#999;padding-left:3px">' . ( $intIndex === 1 ? '[' : '' ) . $strValue . ( $intIndex === count( $arrColumns ) - 1 ? ']' : '' ) . '</span>' );
            $intIndex += 1;
        }

        return \Image::getHtml( $strImage . '.svg', '', '') . ' ' . $strTemplate;
    }

    public static function parseCatalogValue($varValue, $arrField, $arrCatalog=[], $blnStringFormat=false, $blnFastMode=false) {

        if ($varValue === '' || $varValue === null) {
            return $varValue;
        }

        if (!isset($arrField['type'])) {
            return $varValue;
        }

        switch ($arrField['type']) {
            case 'text':
                return $arrField['value'];
                break;
            case 'checkboxWizard':
            case 'checkbox':
            case 'select':
            case 'radio':
                $varValue = !is_array($arrField['value']) ? [$arrField['value']] : $arrField['value'];
                $arrOptionValues =  static::getSelectedOptions($varValue, $arrField['options']);
                if ($blnStringFormat) {
                    return static::parse($arrOptionValues);
                }
                return $arrOptionValues;
                break;
            case 'fileTree':
                $strSizeId = null;
                $arrOrderField = [];
                if ($arrField['orderField'] && $arrCatalog[$arrField['orderField']]) {
                    $arrOrderField = Image::getUuids($arrCatalog[$arrField['orderField']]);
                }
                if (isset($arrField['imageSize']) && $arrField['imageSize']) {
                    $strSizeId = $arrField['imageSize'];
                }
                if ($blnFastMode) {
                    return Image::getUuids($varValue);
                }
                if ($arrField['isImage'] || $arrField['isGallery']) {
                    if ($blnFastMode) {
                        return Image::getUuids($varValue);
                    }
                    $arrImages = [];
                    return Image::getImage($varValue, $strSizeId, $arrImages, $arrOrderField);
                }
                if ($arrField['isFile']) {
                    return File::getFile($varValue);
                }
                return [];
                break;
            case 'multiColumnWizard':
                $arrReturn = [];
                $varEntities = \StringUtil::deserialize($varValue, true);
                foreach ($varEntities as $arrEntity) {
                    $arrRow = [];
                    foreach ($arrEntity as $strField => $strValue) {
                        $arrRow[$strField] = static::parseCatalogValue($strValue, \Widget::getAttributesFromDca($arrField['columnFields'][$strField], $strField, $strValue, $strField, null), $arrCatalog, true, true);;
                    }
                    $arrReturn[] = $arrRow;
                }
                return $arrReturn;
                break;
            case 'pageTree':
                if (!$varValue) {
                    return '';
                }
                $arrValues = [];
                $varValue = explode(',', $varValue);
                foreach ($varValue as $strPageId) {
                    $objPage = \PageModel::findByPk($strPageId);
                    if ($objPage === null) {
                        continue;
                    }
                    $arrValues[$strPageId] = [
                        'url' => $objPage->getFrontendUrl(),
                        'master' => $objPage->getFrontendUrl('/'.$arrCatalog['alias']),
                        'absolute' => $objPage->getAbsoluteUrl('/'.$arrCatalog['alias'])
                    ];
                }
                return $arrValues;
                break;
        }

        return $arrField['value'];
    }

    public static function getSelectedOptions($arrValues, $arrOptions) {

        $arrReturn = [];
        if (!is_array($arrOptions) || !is_array($arrValues)) {
            return $arrReturn;
        }

        $arrTemp = [];
        foreach ($arrOptions as $arrValue) {
            if (in_array($arrValue['value'], $arrValues)) {
                $arrTemp[$arrValue['value']] = $arrValue;
            }
        }

        foreach ($arrValues as $strValue) {
            $arrReturn[] = $arrTemp[$strValue];
        }

        return $arrReturn;
    }

    public function cmp($a, $b){
        $strKey = 'value';
        if($a[$strKey] < $b[$strKey]){
            return 1;
        }else if($a[$strKey] > $b[$strKey]){
            return -1;
        }
        return 0;
    }

    public static function saveGeoCoordinates( $strTable, $arrActiveRecord ) {

        $arrEntity = [];
        if ( !$arrActiveRecord['id'] ) {
            return null;
        }

        foreach ( $arrActiveRecord as $strField => $strValue ) {
            $arrEntity[ $strField ] = static::parseCatalogValue( $strValue, \Widget::getAttributesFromDca( $GLOBALS['TL_DCA'][ $strTable ]['fields'][ $strField ], $strField, $strValue, $strField, $strTable ), $arrActiveRecord, true );
        }

        $objRoleResolver = RoleResolver::getInstance( $strTable, $arrEntity );
        $arrGeoFields = $objRoleResolver->getGeoCodingFields();
        $strAddress = $objRoleResolver->getGeoCodingAddress();
        $objDatabase = \Database::getInstance();
        $objGeoCoding = new \Alnv\ContaoGeoCodingBundle\Library\GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress( 'google-geocoding', $strAddress );

        if (static::isEmpty($arrEntity[$arrGeoFields['longitude']]) && $arrEntity[$arrGeoFields['latitude']]) {
            return null;
        }

        if ( $arrGeoCoding !== null && !empty( $arrGeoFields ) ) {

            $arrSet = [];
            $arrSet[ 'tstamp' ] = time();
            $arrSet[ $arrGeoFields['longitude'] ] = $arrGeoCoding['longitude'];
            $arrSet[ $arrGeoFields['latitude'] ] = $arrGeoCoding['latitude'];
            $objDatabase->prepare( 'UPDATE '. $strTable .' %s WHERE id = ?' )->set( $arrSet )->execute($arrEntity['id']);
        }
    }

    public static function isEmpty($varValue) {
        if ($varValue === null) {
            return true;
        }
        if ($varValue === '') {
            return true;
        }
        return false;
    }

    public function saveAlias( $arrActiveRecord, $arrFields, $arrCatalog ) {

        if ( !$arrActiveRecord['id'] ) {
            return null;
        }

        $arrValues = [];
        $objDatabase = \Database::getInstance();

        foreach ( $arrFields as $strFieldname => $arrField ) {
            if ( !isset( $arrField['eval'] ) ) {
                continue;
            }

            if ( !$arrField['eval']['useAsAlias'] ) {
                continue;
            }

            if ( isset( $arrActiveRecord[ $strFieldname ] ) && $arrActiveRecord[ $strFieldname ] !== '' && $arrActiveRecord[ $strFieldname ] !== null ) {
                $arrValues[] = $arrActiveRecord[ $strFieldname ];
            }
        }

        if ( empty( $arrValues ) ) {
            $strAlias = md5( time() . '/' . ( $arrActiveRecord['id'] ?: '' ) );
        } else {
            $strAlias = implode( '-', $arrValues );
        }

        $arrSet = [];
        $arrSet[ 'tstamp' ] = time();
        $arrSet[ 'alias' ] = self::generateAlias($strAlias, 'alias', $arrCatalog['table'], $arrActiveRecord['id'], $arrActiveRecord['pid']);
        $objDatabase->prepare( 'UPDATE '. $arrCatalog['table'] .' %s WHERE id = ?' )->set($arrSet)->execute($arrActiveRecord['id']);
    }

    public static function generateAlias($strValue,$strAliasField='alias',$strTable=null,$strId=null,$strPid=null) {

        $blnAliasFieldExist = $strTable ? \Database::getInstance()->fieldExists($strAliasField, $strTable) : false;

        if ($strId && $blnAliasFieldExist) {
            $objEntity = \Database::getInstance()->prepare( 'SELECT * FROM ' . $strTable . ' WHERE `id`=?' )->limit(1)->execute($strId);
            if ($objEntity->numRows) {
                $strValue = $objEntity->{$strAliasField} ?: $strValue;
            }
        }
        if (!$strValue) {
            return md5(time());
        }

        $strValidChars = 'a-zA-Z0-9';
        $objCatalog = \Alnv\ContaoCatalogManagerBundle\Models\CatalogModel::findByTableOrModule($strTable);
        if ($objCatalog !== null) {
            if ($objCatalog->validAliasCharacters) {
                $strValidChars = $objCatalog->validAliasCharacters;
            }
        }

        $objSlugGenerator = new \Ausi\SlugGenerator\SlugGenerator((new \Ausi\SlugGenerator\SlugOptions)
            ->setValidChars($strValidChars)
            ->setLocale('de')
            ->setDelimiter('-'));
        $strValue = $objSlugGenerator->generate($strValue);

        if ( strlen( $strValue ) > 100 ) {
            $strValue = substr( $strValue, 0, 100 );
        }

        if ($blnAliasFieldExist && $strId) {
            $arrValues = [$strValue, $strId];
            if ($strPid !== null) {
                $arrValues[] = $strPid;
            }
            if ( \Database::getInstance()->prepare( 'SELECT * FROM ' . $strTable . ' WHERE `'.$strAliasField.'`=? AND `id`!=?' . ( $strPid ? ' AND `pid`=?' : '' ) )->limit(1)->execute( $arrValues )->numRows ) {
                $strValue = $strValue . '-' . $strId;
            }
        }

        return $strValue;
    }

    public static function convertComboWizardToModelValues( $strValue, $strTable = '' ) {

        $arrReturn = [];
        $arrValues = [];
        $arrQueries = [];
        $strName = 'group0';
        $blnInitialGroup = true;
        $arrJson = \Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit::decodeJson( $strValue, [
            'option' => 'field',
            'option2' => 'operator',
            'option3' => 'value',
            'option4' => 'group'
        ]);

        if (!is_array($arrJson) || empty($arrJson)) {
            return $arrReturn;
        }

        foreach ($arrJson as $intIndex => $arrQuery) {

            if ( isset($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]) && $GLOBALS['CM_OPERATORS'][ $arrQuery['operator'] ]['token']) {

                if ($arrQuery['group'] || $blnInitialGroup) {
                    $strName = 'group' . $intIndex;
                }

                if ( !isset( $arrQueries[ $strName ] ) ) {
                    $arrQueries[ $strName ] = [];
                }

                $varValue = $arrQuery['value'];

                if ($varValue !== '' || $varValue !== null) {
                    $objIt = new \InsertTags();
                    $varValue = $objIt->replace($varValue, true);
                }

                $arrColumns = [];
                $varValue = is_array($varValue) ? $varValue : \StringUtil::deserialize($varValue, true);
                foreach ($varValue as $strIndex => $strValue) {
                    if (isset( $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber']) && $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber'] > 1) {
                        if ($strIndex % $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber']) {
                            $arrColumns[] = \StringUtil::parseSimpleTokens($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['token'], [
                                'field' => $strTable . '.' . $arrQuery['field'],
                                'value' => '?'
                            ]);
                        }
                    } else {
                        $arrColumns[] = \StringUtil::parseSimpleTokens( $GLOBALS['CM_OPERATORS'][ $arrQuery['operator'] ]['token'], [
                            'field' => $strTable . '.' . $arrQuery['field'],
                            'value' => '?'
                        ]);
                    }

                    $arrValues[] = $strValue;
                }

                if ( !empty( $arrColumns ) ) {
                    if ( count( $arrColumns ) > 1 ) {
                        $strColumn = '(' . implode( ' OR ', $arrColumns ) . ')';
                    } else {
                        $strColumn = $arrColumns[0];
                    }
                    $arrQueries[ $strName ][] = $strColumn;
                }

                if ( $arrQuery['group'] ) {
                    $blnInitialGroup = false;
                }
            }
        }

        $arrReturn['column'] = [];
        $arrReturn['value'] = $arrValues;

        foreach ( $arrQueries as $arrQuery ) {

            if ( empty( $arrQuery ) ) {
                continue;
            }
            if ( count( $arrQuery ) > 1 ) {
                $arrReturn['column'][] = '(' . implode( ' OR ', $arrQuery ) . ')';
            } else {
                $arrReturn['column'][] = $arrQuery[0];
            }
        }

        return $arrReturn;
    }

    public static function getTableByDo() {

        if ( !\Input::get('do') ) {
            return null;
        }

        if ( \Input::get('do') && \Input::get('table') ) {
            return \Input::get('table');
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog( \Input::get('do') );
        return $objCatalog->getCatalog()['table'];
    }

    public static function convertArrayItemsToPlaceholders($arrArray, $strPlaceholder='?') {

        return implode(",", array_fill(0, count($arrArray), $strPlaceholder));
    }
}