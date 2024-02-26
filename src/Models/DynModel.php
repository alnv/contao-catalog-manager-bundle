<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Contao\Database;
use Contao\DcaExtractor;
use Contao\Model;

class DynModel extends Model
{

    public static $strTable = '';

    // public static $arrClassNames = [];

    public function __construct($objResult = null)
    {

        if (!static::$strTable) {
            return null;
        }

        parent::__construct($objResult);
    }

    public function createDynTable($strTable, $objResult = null)
    {

        static::$strTable = $strTable;
        // static::$arrClassNames[$strTable] = 'Alnv\ContaoCatalogManagerBundle\Models\DynModel';

        parent::__construct($objResult);
    }

    public static function findByIdOrAlias($varId, array $arrOptions = [])
    {

        $t = static::$strTable;

        if (!isset($arrOptions['column']) || !is_array($arrOptions['column'])) {
            $arrOptions['column'] = [];
        }

        if (!isset($arrOptions['value']) || !is_array($arrOptions['value'])) {
            $arrOptions['value'] = [];
        }

        $arrOptions['column'][] = !preg_match('/^[1-9]\d*$/', $varId) ? "$t.alias=?" : "$t.id=?";
        $arrOptions['value'][] = $varId;
        $arrOptions['limit'] = 1;
        $arrOptions['return'] = 'Model';

        return static::find($arrOptions);
    }

    protected static function buildFindQuery(array $arrOptions)
    {

        $objBase = DcaExtractor::getInstance($arrOptions['table']);
        $strDistanceSelection = '';

        if (isset($arrOptions['distance'])) {

            $strDistanceSelection = sprintf(
                ",3956 * 1.6 * 2 * ASIN(SQRT(POWER(SIN((%s-abs(%s.`%s`)) * pi()/180 / 2),2) + COS(%s * pi()/180) * COS(abs(%s.`%s`) *  pi()/180) * POWER( SIN( (%s-%s.`%s`) *  pi()/180 / 2 ), 2 ))) AS _distance ",
                $arrOptions['distance']['latCord'],
                $arrOptions['table'],
                $arrOptions['distance']['latField'],
                $arrOptions['distance']['latCord'],
                $arrOptions['table'],
                $arrOptions['distance']['latField'],
                $arrOptions['distance']['lngCord'],
                $arrOptions['table'],
                $arrOptions['distance']['lngField']
            );
        }

        if (!$objBase->hasRelations()) {
            $strQuery = "SELECT *$strDistanceSelection FROM " . $arrOptions['table'];
        } else {

            $arrJoins = [];
            $arrFields = [$arrOptions['table'] . ".*"];
            $intCount = 0;

            foreach ($objBase->getRelations() as $strKey => $arrConfig) {

                if ((isset($arrConfig['load']) && $arrConfig['load'] == 'eager') || (isset($arrOptions['eager']) && $arrOptions['eager'])) {

                    if ($arrConfig['type'] == 'hasOne' || $arrConfig['type'] == 'belongsTo') {

                        ++$intCount;
                        $objRelated = DcaExtractor::getInstance($arrConfig['table']);

                        foreach (array_keys($objRelated->getFields()) as $strField) {
                            $arrFields[] = 'j' . $intCount . '.' . Database::quoteIdentifier($strField) . ' AS ' . $strKey . '__' . $strField;
                        }

                        $arrJoins[] = " LEFT JOIN " . $arrConfig['table'] . " j$intCount ON " . $arrOptions['table'] . "." . Database::quoteIdentifier($strKey) . "=j$intCount." . $arrConfig['field'];
                    }
                }
            }

            $strQuery = "SELECT " . implode(', ', $arrFields) . $strDistanceSelection . " FROM " . $arrOptions['table'] . implode("", $arrJoins);
        }

        if (isset($arrOptions['column'])) {
            $strQuery .= " WHERE " . (is_array($arrOptions['column']) ? implode(" AND ", $arrOptions['column']) : $arrOptions['table'] . '.' . Database::quoteIdentifier($arrOptions['column']) . "=?");
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

        return $strQuery;
    }
}