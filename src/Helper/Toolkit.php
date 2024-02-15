<?php

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Library\RoleResolver;

class Toolkit
{

    public static function addToCatalogData($strType, $strTable, $strIdentifier)
    {

        $objCatalogData = \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel::getByTypeAndTableAndIdentifier($strType, $strTable, $strIdentifier);

        if (!$objCatalogData) {
            $objCatalogData = new \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel();
            $objCatalogData->created_at = time();
            $objCatalogData->type = $strType;
            $objCatalogData->table = $strTable;
            $objCatalogData->identifier = $strIdentifier;
            $objCatalogData->session = self::getSessionId();
            $objCatalogData->member = (\FrontendUser::getInstance()->id ?: 0);
        }

        $objCatalogData->tstamp = time();

        return $objCatalogData->save()->row();
    }

    public static function addCount($strType, $strTable, $strIdentifier)
    {

        $intDay = (new \Date())->dayBegin;
        $intMonth = (new \Date())->monthBegin;
        $intYear = (new \Date())->yearBegin;

        $arrAssigns = [
            'day' => \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel::getByTypeAndTableIdentifierAndDayPeriod($strType, $strTable, $strIdentifier, $intDay),
            'month' => \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel::getByTypeAndTableIdentifierAndMonthPeriod($strType, $strTable, $strIdentifier, $intMonth),
            'year' => \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel::getByTypeAndTableIdentifierAndYearPeriod($strType, $strTable, $strIdentifier, $intYear)
        ];

        foreach ($arrAssigns as $strPeriod => $objEntity) {

            if (!$objEntity) {
                $objEntity = new \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel();
                $objEntity->created_at = time();
                $objEntity->type = $strType;
                $objEntity->table = $strTable;
                $objEntity->identifier = $strIdentifier;
                $objEntity->count = 1;
                switch ($strPeriod) {
                    case 'day':
                        $objEntity->day = $intDay;
                        break;
                    case 'month':
                        $objEntity->month = $intMonth;
                        break;
                    case 'year':
                        $objEntity->year = $intYear;
                        break;
                }
            } else {
                $objEntity->count++;
            }

            $objEntity->tstamp = time();
            $objEntity->save();
        }
    }

    public static function getLastAddedByTypeAndTable($strType, $strTable)
    {

        $arrIds = [];
        $objData = \Alnv\ContaoCatalogManagerBundle\Models\CatalogDataModel::getLastAddedByType('view-master');
        if (!$objData) {
            return $arrIds;
        }
        while ($objData->next()) {
            if ($objData->table != $strTable) {
                continue;
            }
            $arrIds[] = $objData->identifier;
        }
        return array_filter($arrIds);
    }

    public static function getSessionId()
    {

        $objSession = \System::getContainer()->get('session');
        $strSessionId = $objSession->get('catalog-session-id');

        if (!$strSessionId) {
            $strSessionId = substr(md5(uniqid() . '.' . time()), 0, 64);
            $objSession->set('catalog-session-id', $strSessionId);
        }

        return $strSessionId;
    }

    public static function getLabel($strItem)
    {

        if (!isset($GLOBALS['TL_LANG']['MSC'][$strItem])) {
            return $strItem;
        }

        return is_array($GLOBALS['TL_LANG']['MSC'][$strItem]) ? $GLOBALS['TL_LANG']['MSC'][$strItem][0] : $GLOBALS['TL_LANG']['MSC'][$strItem];
    }

    public static function parse($varValue, $strDelimiter = ', ', $strField = 'label')
    {

        if (is_array($varValue)) {
            $arrValues = array_map(function ($arrValue) use ($strField) {
                return is_array($arrValue) ? $arrValue[$strField] : $arrValue;
            }, $varValue);
            return implode($strDelimiter, $arrValues);
        }

        return $varValue;
    }

    public static function compress($strTemplate)
    {

        $strTemplate = str_replace(["\r\n", "\r"], "\n", $strTemplate);
        $arrLines = explode("\n", $strTemplate);
        $arrNewLines = [];

        foreach ($arrLines as $strLine) {
            if (!empty($strLine))
                $arrNewLines[] = trim($strLine);
        }

        return implode('', $arrNewLines);
    }

    public static function getFilterValue($strField)
    {

        $arrActiveRecord = [];
        if (\Cache::has('activeRecord')) {
            $arrActiveRecord = \Cache::get('activeRecord') ?: [];
        }
        $varValue = \Input::get($strField) ?: \Input::post($strField);

        if (!$varValue && !empty($arrActiveRecord)) {
            $varValue = $arrActiveRecord[$strField];
        }

        if (is_array($varValue)) {
            $varValue = array_filter($varValue);
        }

        return $varValue;
    }

    public static function getSqlTypes()
    {

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

    public static function getRgxp($strType, $arrOptions = [])
    {

        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(null);
        $arrRole = $objRoleResolver->getRole($arrOptions['role']);

        if (isset($arrRole['rgxp']) && $arrRole['rgxp']) {

            return $arrRole['rgxp'];
        }

        return '';
    }

    public static function getSql($strType, $arrOptions = [])
    {

        $objRoleResolver = \Alnv\ContaoCatalogManagerBundle\Library\RoleResolver::getInstance(null);
        $arrRole = $objRoleResolver->getRole($arrOptions['role']);

        if (isset($arrRole['sql']) && $arrRole['sql']) {
            return sprintf($arrRole['sql'], (isset($arrOptions['default']) && $arrOptions['default'] ? $arrOptions['default'] : ''));
        }

        $arrSql = static::getSqlTypes();

        if ($arrOptions['multiple']) {
            return $arrSql['blob'];
        }

        switch ($strType) {
            case 'color':
                return sprintf($arrSql['vc8'], ($arrOptions['default'] ?: ''));
            case 'date':
                return sprintf($arrSql['i10NullAble'], ($arrOptions['default'] ?: ''));
            default:
                return $arrSql['blob'];
        }
    }

    public static function parseDetailLink($varPage, $strAlias)
    {

        if (is_numeric($varPage) && $varPage) {
            $objPage = \PageModel::findByPk($varPage);
            if ($objPage !== null) {
                return $objPage->getFrontendUrl(($strAlias ? '/' . $strAlias : ''));
            }
        }

        if (is_array($varPage) && isset($varPage['id'])) {
            $objPage = \PageModel::findByPk($varPage['id']);
            if ($objPage !== null) {
                return $objPage->getFrontendUrl(($strAlias ? '/' . $strAlias : ''));
            }
        }

        if ($varPage instanceof \PageModel) {
            return $varPage->getFrontendUrl(($strAlias ? '/' . $strAlias : ''));
        }

        return '';
    }

    public static function parseImage($varImage)
    {
        if (!is_array($varImage) && (\Validator::isBinaryUuid($varImage) || \Validator::isUuid($varImage))) {
            $objFile = \FilesModel::findByUuid($varImage);
            if ($objFile !== null) {
                return $objFile->path;
            }
        }
        if (!is_array($varImage) && empty($varImage)) {
            return '';
        }
        if (isset($varImage['img'])) {
            return $varImage['img']['src'];
        }
        return $varImage[0]['img']['src'];
    }

    public static function parseParametersFromString($strParameter)
    {

        $arrChunks = explode('?', urldecode($strParameter), 2);
        $strSource = \StringUtil::decodeEntities($arrChunks[1]);
        $strSource = str_replace('[&]', '&', $strSource);

        return explode('&', $strSource);
    }

    public static function getValueFromUrl($arrValue)
    {

        if ($arrValue === '' || $arrValue === null) {
            return '';
        }

        if (is_array($arrValue)) {
            return serialize($arrValue);
        }

        return $arrValue;
    }

    public static function getOrderByStatementFromArray($arrOrders)
    {

        if (isset($arrOrders['field']) && isset($arrOrders['order'])) {
            return $arrOrders['field'] . ' ' . $arrOrders['order'];
        }

        return implode(',', array_filter(array_map(function ($arrOrder) {
            if (!isset($arrOrder['field']) || !$arrOrder['field']) {
                return '';
            }
            $arrOrder['order'] = $arrOrder['order'] ?? 'ASC';
            return $arrOrder['field'] . ' ' . $arrOrder['order'];
        }, $arrOrders)));
    }

    public static function renderRow($arrRow, $arrLabelFields, $arrCatalog, $arrFields)
    {

        $arrColumns = [];

        if (isset($arrCatalog['flagField']) && $arrCatalog['flagField'] && $arrCatalog['showColumns']) {
            if (!in_array($arrCatalog['flagField'], $arrColumns)) {
                $arrLabelFields[] = $arrCatalog['sortingType'] !== 'none' ? $arrCatalog['flagField'] : '-';
            }
        }

        foreach ($arrLabelFields as $strField) {

            if (!isset($arrRow[$strField])) {
                $arrColumns[$strField] = '';
                continue;
            }

            $arrColumns[$strField] = static::parseCatalogValue($arrRow[$strField], \Widget::getAttributesFromDca($arrFields[$strField], $strField, $arrRow[$strField], $strField, $arrCatalog['table']), $arrRow, true);
            if (isset($arrFields[$strField]['eval']['role']) && $arrFields[$strField]['eval']['role']) {
                switch ($arrFields[$strField]['eval']['role']) {
                    case 'redirects':
                    case 'pages':
                    case 'page':
                        if (!is_array($arrColumns[$strField])) {
                            break;
                        }
                        $arrPages = [];
                        $arrPageIds = array_keys($arrColumns[$strField]);
                        foreach ($arrPageIds as $strPageId) {
                            if ($objPage = \PageModel::findByPk($strPageId)) {
                                $arrPages[] = $objPage->pageTitle ?: $objPage->title;
                            }
                        }
                        $arrColumns[$strField] = implode(', ', $arrPages);
                        break;
                }
            }
        }

        if (count($arrColumns) < 2 && $arrCatalog['showColumns']) {
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

        if (is_array($arrLabels) && !empty($arrLabels)) {
            $strTemplate .= '<span style="color:#999;padding-left:3px">(' . implode(' - ', $arrLabels) . ')</span>' . '</div>';
        }

        return $strTemplate;
    }

    public static function renderTreeRow($arrRow, $strLabel, $arrLabelFields, $arrCatalog, $arrFields)
    {

        $intIndex = 0;
        $arrColumns = [];
        $strTemplate = '';
        $strImage = 'articles';

        foreach ($arrLabelFields as $strField) {
            $arrColumns[$strField] = static::parseCatalogValue($arrRow[$strField], \Widget::getAttributesFromDca($arrFields[$strField], $strField, $arrRow[$strField], $strField, $arrCatalog['table']), $arrRow, true);
        }

        if (count($arrColumns) < 2) {
            return array_values($arrColumns)[0];
        }

        foreach ($arrColumns as $strField => $strValue) {
            $strTemplate .= !$intIndex ? $strValue : (' <span class="' . $strField . '" style="color:#999;padding-left:3px">' . ($intIndex === 1 ? '[' : '') . $strValue . ($intIndex === count($arrColumns) - 1 ? ']' : '') . '</span>');
            $intIndex += 1;
        }

        return \Image::getHtml($strImage . '.svg', '', '') . ' ' . $strTemplate;
    }

    public static function parseCatalogValue($varValue, $arrField, $arrCatalog = [], $blnStringFormat = false, $blnFastMode = false, $blnIsForm = false)
    {

        if ($varValue === '' || $varValue === null) {
            return $varValue;
        }
        if (!isset($arrField['type']) && !$arrField['value']) {
            return $varValue;
        }
        switch ($arrField['type']) {
            case 'text':
                return $arrField['value'];
            case 'checkboxWizard':
            case 'checkbox':
            case 'select':
            case 'radio':
                if (isset($arrField['csv']) && $arrField['csv'] && is_string($arrField['value'])) {
                    $arrField['value'] = explode($arrField['csv'], $arrField['value']);
                }
                $varValue = !is_array($arrField['value']) ? \StringUtil::deserialize($arrField['value'], true) : $arrField['value'];
                $arrOptionValues = static::getSelectedOptions($varValue, ($arrField['options']??[]));
                if ($blnStringFormat) {
                    return static::parse($arrOptionValues);
                }
                if ($blnIsForm && $arrField['type'] == 'checkbox' && $arrField['multiple'] == false) {
                    return $arrField['value'];
                }
                return $arrOptionValues;
            case 'fileTree':
                $strSizeId = null;
                $arrOrderField = [];
                if (isset($arrField['orderField']) && $arrField['orderField'] && $arrCatalog[$arrField['orderField']]) {
                    $arrOrderField = Image::getUuids($arrCatalog[$arrField['orderField']]);
                }
                if (isset($arrField['imageSize']) && $arrField['imageSize']) {
                    $strSizeId = $arrField['imageSize'];
                }
                if ($blnFastMode) {
                    return Image::getUuids($varValue);
                }
                if ((isset($arrField['isImage']) && $arrField['isImage']) || (isset($arrField['isGallery']) && $arrField['isGallery'])) {

                    $arrImages = [];
                    return Image::getImage($varValue, $strSizeId, $arrImages, $arrOrderField);
                }

                if ($arrField['isFile']) {
                    $arrFiles = [];
                    return File::getFile($varValue, $arrFiles, $arrOrderField);
                }
                return [];
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
                    if ($blnStringFormat) {
                        $arrValues[] = $objPage->pageTitle ?: $objPage->title;
                    } else {
                        $strUrl = '';
                        try {
                            $strUrl = $objPage->getFrontendUrl();
                        } catch (\Exception $objException) {}
                        $arrValues[$strPageId] = [
                            'url' => $strUrl,
                            'master' => $objPage->getFrontendUrl('/' . $arrCatalog['alias']),
                            'absolute' => $objPage->getAbsoluteUrl('/' . $arrCatalog['alias'])
                        ];
                    }
                }
                if ($blnStringFormat) {
                    return implode(', ', $arrValues);
                }
                return $arrValues;
        }

        return $arrField['value'];
    }

    public static function getSelectedOptions($arrValues, $arrOptions)
    {

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
            if (!isset($arrTemp[$strValue])) {
                continue;
            }
            $arrReturn[] = $arrTemp[$strValue];
        }

        return $arrReturn;
    }

    public function cmp($a, $b)
    {
        $strKey = 'value';
        if ($a[$strKey] < $b[$strKey]) {
            return 1;
        } else if ($a[$strKey] > $b[$strKey]) {
            return -1;
        }
        return 0;
    }

    public static function saveGeoCoordinates($strTable, $arrActiveRecord)
    {

        $arrEntity = [];
        if (!$arrActiveRecord['id']) {
            return null;
        }

        foreach ($arrActiveRecord as $strField => $strValue) {
            $arrEntity[$strField] = static::parseCatalogValue($strValue, \Widget::getAttributesFromDca($GLOBALS['TL_DCA'][$strTable]['fields'][$strField], $strField, $strValue, $strField, $strTable), $arrActiveRecord, true);
        }

        $objRoleResolver = RoleResolver::getInstance($strTable, $arrEntity);
        $arrGeoFields = $objRoleResolver->getGeoCodingFields();
        $strAddress = $objRoleResolver->getGeoCodingAddress();
        $objDatabase = \Database::getInstance();
        $objGeoCoding = new \Alnv\ContaoGeoCodingBundle\Library\GeoCoding();
        $arrGeoCoding = $objGeoCoding->getGeoCodingByAddress('google-geocoding', $strAddress);

        if (static::isEmpty($arrEntity[$arrGeoFields['longitude']]) && $arrEntity[$arrGeoFields['latitude']]) {
            return null;
        }

        if ($arrGeoCoding !== null && !empty($arrGeoFields)) {
            $arrSet = [];
            $arrSet['tstamp'] = time();
            $arrSet[$arrGeoFields['longitude']] = $arrGeoCoding['longitude'];
            $arrSet[$arrGeoFields['latitude']] = $arrGeoCoding['latitude'];
            $objDatabase->prepare('UPDATE ' . $strTable . ' %s WHERE id = ?')->set($arrSet)->execute($arrEntity['id']);
        }
    }

    public static function isEmpty($varValue)
    {
        if ($varValue === null) {
            return true;
        }
        if ($varValue === '') {
            return true;
        }
        return false;
    }

    public static function saveAlias($arrActiveRecord, $arrFields, $arrCatalog)
    {

        if (!$arrActiveRecord['id']) {
            return null;
        }

        $arrValues = [];
        $objDatabase = \Database::getInstance();

        foreach ($arrFields as $strFieldname => $arrField) {

            if (!isset($arrField['eval'])) {
                continue;
            }

            if (!isset($arrField['eval']['useAsAlias']) || !$arrField['eval']['useAsAlias']) {
                continue;
            }

            if (isset($arrActiveRecord[$strFieldname]) && $arrActiveRecord[$strFieldname] !== '' && $arrActiveRecord[$strFieldname] !== null) {
                $arrValues[] = $arrActiveRecord[$strFieldname];
            }
        }

        if (empty($arrValues)) {
            $strAlias = md5(time() . '/' . $arrActiveRecord['id']);
        } else {
            $strAlias = implode('-', $arrValues);
        }

        $arrSet = [];
        $arrSet['tstamp'] = time();
        $arrSet['alias'] = self::generateAlias($strAlias, 'alias', $arrCatalog['table'], $arrActiveRecord['id'], ($arrActiveRecord['pid'] ?: null), 'a-zA-Z0-9', ($arrActiveRecord['lid'] ?: null));
        $objDatabase->prepare('UPDATE ' . $arrCatalog['table'] . ' %s WHERE id = ?')->set($arrSet)->execute($arrActiveRecord['id']);
    }

    public static function generateAlias($strValue, $strAliasField = 'alias', $strTable = null, $strId = null, $strPid = null, $strValidChars = 'a-zA-Z0-9', $strLid = null)
    {

        $strLanguage = '';
        $strLanguageColumn = $GLOBALS['TL_DCA'][$strTable]['config']['langColumnName'] ?? '';
        $strLangPidColumn = $GLOBALS['TL_DCA'][$strTable]['config']['langPid'] ?? '';

        $blnAliasFieldExist = $strTable && \Database::getInstance()->fieldExists($strAliasField, $strTable);

        if ($strId && $blnAliasFieldExist) {
            $objEntity = \Database::getInstance()->prepare('SELECT * FROM ' . $strTable . ' WHERE `id`=?')->limit(1)->execute($strId);
            if ($objEntity->numRows) {
                if ($strLanguageColumn) {
                    $strLanguage = $objEntity->{$strLanguageColumn} ?: '';
                }
                $strValue = $objEntity->{$strAliasField} ?: $strValue;
            }
        }

        if (!$strValue) {
            return md5(time());
        }

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

        if (strlen($strValue) > 120) {
            $strValue = substr($strValue, 0, 120);
        }

        if ($blnAliasFieldExist && $strId) {

            $arrQueries = [];
            $arrValues = [$strValue, $strId];

            if ($strPid !== null) {
                $arrQueries[] = ' AND `pid`=?';
                $arrValues[] = $strPid;
            }

            if ($strLanguageColumn && $strLangPidColumn) {
                $arrQueries[] = ' AND `'.$strLangPidColumn.'`!=? AND id!=?';
                $arrValues[] = ($strLid?:$strId);
                $arrValues[] = $strLid;
            }

            if (\Database::getInstance()->prepare('SELECT * FROM ' . $strTable . ' WHERE `' . $strAliasField . '`=? AND `id`!=?' . implode('', $arrQueries))->limit(1)->execute($arrValues)->numRows) {
                $strValue = $strValue . '-' . $strId;
            }
        }

        return $strValue;
    }

    public static function convertComboWizardToModelValues($strValue, $strTable = '')
    {

        $arrReturn = [];
        $arrValues = [];
        $arrQueries = [];
        $strName = 'group0';
        $blnInitialGroup = true;

        if (is_string($strValue)) {
            $strValue = \StringUtil::decodeEntities($strValue);
        }

        $arrJson = \Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit::decodeJson($strValue, [
            'option' => 'field',
            'option2' => 'operator',
            'option3' => 'value',
            'option4' => 'group'
        ]);

        if (!is_array($arrJson) || empty($arrJson)) {
            return $arrReturn;
        }

        foreach ($arrJson as $intIndex => $arrQuery) {

            if (isset($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]) && $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['token']) {

                if ((isset($arrQuery['group']) && $arrQuery['group']) || $blnInitialGroup) {
                    $strName = 'group' . $intIndex;
                }

                if (!isset($arrQueries[$strName])) {
                    $arrQueries[$strName] = [];
                }

                $varValue = $arrQuery['value'];

                if ($varValue !== '' || $varValue !== null) {
                    $objIt = new \InsertTags();
                    $varValue = $objIt->replace($varValue, true);
                }

                $arrColumns = [];
                $varValue = is_array($varValue) ? $varValue : \StringUtil::deserialize($varValue, true);
                foreach ($varValue as $strIndex => $strValue) {

                    if (isset($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber']) && $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber'] > 1) {
                        if ($strIndex % $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['valueNumber']) {
                            $arrColumns[] = \StringUtil::parseSimpleTokens($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['token'], [
                                'field' => ($strTable ? $strTable . '.' : '') . $arrQuery['field'],
                                'value' => '?'
                            ]);
                        }
                    } else {
                        $arrColumns[] = \StringUtil::parseSimpleTokens($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['token'], [
                            'field' => ($strTable ? $strTable . '.' : '') . $arrQuery['field'],
                            'value' => '?'
                        ]);
                    }

                    if (isset($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['empty']) && is_bool($GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['empty']) && $GLOBALS['CM_OPERATORS'][$arrQuery['operator']]['empty'] == true) {
                        continue;
                    }

                    $arrValues[] = $strValue;
                }

                if (!empty($arrColumns)) {
                    if (count($arrColumns) > 1) {
                        $strColumn = '(' . implode(' OR ', $arrColumns) . ')';
                    } else {
                        $strColumn = $arrColumns[0];
                    }
                    $arrQueries[$strName][] = $strColumn;
                }

                if (isset($arrQuery['group']) && $arrQuery['group']) {
                    $blnInitialGroup = false;
                }
            }
        }

        $arrReturn['column'] = [];
        $arrReturn['value'] = $arrValues;

        foreach ($arrQueries as $arrQuery) {

            if (empty($arrQuery)) {
                continue;
            }
            if (count($arrQuery) > 1) {
                $arrReturn['column'][] = '(' . implode(' OR ', $arrQuery) . ')';
            } else {
                $arrReturn['column'][] = $arrQuery[0];
            }
        }

        return $arrReturn;
    }

    public static function getTableByDo()
    {

        if (!\Input::get('do')) {
            return null;
        }

        if (\Input::get('do') && \Input::get('table')) {
            return \Input::get('table');
        }

        $objCatalog = new \Alnv\ContaoCatalogManagerBundle\Library\Catalog(\Input::get('do'));
        return $objCatalog->getCatalog()['table'];
    }

    public static function convertArrayItemsToPlaceholders($arrArray, $strPlaceholder = '?')
    {

        return implode(",", array_fill(0, count($arrArray), $strPlaceholder));
    }
}