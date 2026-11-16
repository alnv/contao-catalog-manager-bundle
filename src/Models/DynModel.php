<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
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
        if (static::isBaseModel()) {
            static::resolveBaseModelTable();
        }

        parent::__construct($objResult);
    }

    public static function createDynTable($strTable, $objResult = null)
    {
        if (!static::isBaseModel() && static::$strTable === $strTable) {
            return new static($objResult);
        }

        $strClass = ModelWizard::getModelClassForTable(static::class, (string)$strTable);

        return new $strClass($objResult);
    }

    public static function findByIdOrAlias($varId, array $arrOptions = [])
    {
        if (!isset($arrOptions['column']) || !is_array($arrOptions['column'])) {
            $arrOptions['column'] = [];
        }

        if (!isset($arrOptions['value']) || !is_array($arrOptions['value'])) {
            $arrOptions['value'] = [];
        }

        [$strCondition, $arrValues] = static::buildIdOrAliasCondition(!\preg_match('/^[1-9]\d*$/', (string)$varId) ? 'alias' : 'id', $varId);

        $arrOptions['column'][] = $strCondition;
        \array_push($arrOptions['value'], ...$arrValues);
        $arrOptions['limit'] = 1;
        $arrOptions['return'] = 'Model';

        return static::find($arrOptions);
    }

    public static function countWithOptions(array $arrOptions = []): int
    {
        unset($arrOptions['limit'], $arrOptions['offset'], $arrOptions['order']);

        $arrOptions['table'] = static::$strTable;

        $strQuery = 'SELECT COUNT(*) AS cnt FROM (' . static::buildFindQuery($arrOptions) . ') AS cm_count';

        $arrValues = [];
        if (isset($arrOptions['value'])) {
            $arrValues = \is_array($arrOptions['value']) ? array_values($arrOptions['value']) : [$arrOptions['value']];
        }

        return (int)Database::getInstance()->prepare($strQuery)->execute(...$arrValues)->cnt;
    }

    protected static function isBaseModel(): bool
    {
        return static::class === self::class;
    }

    protected static function resolveBaseModelTable(): void
    {
        if (!static::$strTable && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            static::$strTable = Toolkit::getTableByDo();
        }

        if (($GLOBALS['CM_TEMP_MODEL_TABLE'] ?? null) && static::$strTable !== $GLOBALS['CM_TEMP_MODEL_TABLE']) {
            static::$strTable = $GLOBALS['CM_TEMP_MODEL_TABLE'];
        }
    }

    protected static function buildIdOrAliasCondition(string $strColumn, $varId): array
    {
        $t = static::$strTable;

        return ["$t.$strColumn=?", [$varId]];
    }

    protected static function assertValidTable($strTable): void
    {
        if (!\is_string($strTable) || !\preg_match('/^[A-Za-z0-9_]{1,64}$/', $strTable)) {
            throw new \InvalidArgumentException('Invalid table name.');
        }
    }

    protected static function buildDistanceSelection(array $arrOptions, string $strTable): string
    {
        if (!isset($arrOptions['distance'])) {
            return '';
        }

        $dist = $arrOptions['distance'];
        $strTableEscaped = Database::quoteIdentifier($strTable);
        $strLatFieldEscaped = Database::quoteIdentifier($dist['latField']);
        $strLngFieldEscaped = Database::quoteIdentifier($dist['lngField']);

        return sprintf(
            "6371 * 2 * ASIN(SQRT(
                POWER(SIN((%F - ABS(%s.%s)) * PI() / 180 / 2), 2) + 
                COS(%F * PI() / 180) * COS(ABS(%s.%s) * PI() / 180) * POWER(SIN((%F - %s.%s) * PI() / 180 / 2), 2)
            )) AS _distance",
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

    protected static function buildFindQuery(array $arrOptions)
    {
        $strTable = $arrOptions['table'];

        static::assertValidTable($strTable);

        $objBase = DcaExtractor::getInstance($strTable);
        $strDistanceSelection = static::buildDistanceSelection($arrOptions, $strTable);

        if ($strDistanceSelection !== '') {
            $strDistanceSelection = ', ' . $strDistanceSelection . ' ';
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