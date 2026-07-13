<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\Database;
use Contao\DcaExtractor;
use Contao\Model;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

class DynModel extends Model
{

    public static $strTable = '';

    public function __construct($objResult = null)
    {
        if (System::getContainer()
                ->get('contao.routing.scope_matcher')
                ->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')) && !static::$strTable) {
            self::$strTable = Toolkit::getTableByDo();
        }

        if (($GLOBALS['CM_TEMP_MODEL_TABLE'] && self::$strTable !== $GLOBALS['CM_TEMP_MODEL_TABLE'])) {
            self::$strTable = $GLOBALS['CM_TEMP_MODEL_TABLE'];
        }

        parent::__construct($objResult);
    }

    public static function createDynTable($strTable, $objResult = null)
    {
        self::$strTable = $strTable;

        return new self($objResult);
    }

    public static function findByIdOrAlias($varId, array $arrOptions = [])
    {
        $t = self::$strTable;

        if (!isset($arrOptions['column']) || !is_array($arrOptions['column'])) {
            $arrOptions['column'] = [];
        }

        if (!isset($arrOptions['value']) || !is_array($arrOptions['value'])) {
            $arrOptions['value'] = [];
        }

        $arrOptions['column'][] = !\preg_match('/^[1-9]\d*$/', $varId) ? "$t.alias=?" : "$t.id=?";
        $arrOptions['value'][] = $varId;
        $arrOptions['limit'] = 1;
        $arrOptions['return'] = 'Model';

        return self::find($arrOptions);
    }

    protected static function buildFindQuery(array $arrOptions): string
    {
        $strTable = $arrOptions['table'];
        $objBase = DcaExtractor::getInstance($strTable);
        $strDistanceSelection = '';

        if (isset($arrOptions['distance'])) {
            $dist = $arrOptions['distance'];
            $strTableEscaped = Database::quoteIdentifier($strTable);
            $strLatFieldEscaped = Database::quoteIdentifier($dist['latField']);
            $strLngFieldEscaped = Database::quoteIdentifier($dist['lngField']);

            $strDistanceSelection = sprintf(
                ", 6371 * 2 * ASIN(SQRT(
                POWER(SIN((%F - ABS(%s.%s)) * PI() / 180 / 2), 2) + 
                COS(%F * PI() / 180) * COS(ABS(%s.%s) * PI() / 180) * POWER(SIN((%F - %s.%s) * PI() / 180 / 2), 2)
            )) AS _distance ",
                (float)$dist['latCord'],
                $strTableEscaped,
                $strLatFieldEscaped,
                (float)$dist['latCord'],
                $strTableEscaped,
                $strLatFieldEscaped,
                (float)$dist['lngCord'],
                $strTableEscaped,
                $strLngFieldEscaped
            );
        }

        if (!$objBase->hasRelations()) {
            $strQuery = "SELECT *" . $strDistanceSelection . " FROM " . Database::quoteIdentifier($strTable);
        } else {
            $arrJoins = [];
            $arrFields = [Database::quoteIdentifier($strTable) . ".*"];
            $intCount = 0;
            $isEagerGlobal = (bool)($arrOptions['eager'] ?? false);

            foreach ($objBase->getRelations() as $strKey => $arrConfig) {
                $isEagerLoad = ($arrConfig['load'] ?? '') === 'eager';

                if ($isEagerLoad || $isEagerGlobal) {
                    if (in_array($arrConfig['type'], ['hasOne', 'belongsTo'], true)) {
                        ++$intCount;
                        $strAlias = 'j' . $intCount;
                        $objRelated = DcaExtractor::getInstance($arrConfig['table']);

                        foreach (array_keys($objRelated->getFields()) as $strField) {
                            $arrFields[] = $strAlias . '.' . Database::quoteIdentifier($strField) . ' AS ' . Database::quoteIdentifier($strKey . '__' . $strField);
                        }

                        $arrJoins[] = sprintf(
                            " LEFT JOIN %s %s ON %s.%s = %s.%s",
                            Database::quoteIdentifier($arrConfig['table']),
                            $strAlias,
                            Database::quoteIdentifier($strTable),
                            Database::quoteIdentifier($strKey),
                            $strAlias,
                            Database::quoteIdentifier($arrConfig['field'])
                        );
                    }
                }
            }

            $strQuery = "SELECT " . implode(', ', $arrFields) . $strDistanceSelection . " FROM " . Database::quoteIdentifier($strTable) . implode("", $arrJoins);
        }

        if (isset($arrOptions['column'])) {
            if (\is_array($arrOptions['column'])) {
                $strQuery .= " WHERE " . implode(" AND ", $arrOptions['column']);
            } else {
                $strQuery .= " WHERE " . Database::quoteIdentifier($strTable) . '.' . Database::quoteIdentifier($arrOptions['column']) . "=?";
            }
        }

        if (isset($arrOptions['group'])) {
            $strQuery .= " GROUP BY " . $arrOptions['group'];
        }

        if (isset($arrOptions['having'])) {
            $strQuery .= " HAVING " . $arrOptions['having'];
        }

        if (isset($arrOptions['order'])) {
            $strQuery .= " ORDER BY " . $arrOptions['order'];
        }
        if (isset($GLOBALS['TL_HOOKS']['dynModelBuildFindQuery']) && \is_array($GLOBALS['TL_HOOKS']['dynModelBuildFindQuery'])) {
            foreach ($GLOBALS['TL_HOOKS']['dynModelBuildFindQuery'] as $arrCallback) {
                $strQuery = System::importStatic($arrCallback[0])->{$arrCallback[1]}($strQuery, $arrOptions);
            }
        }

        return $strQuery;
    }
}