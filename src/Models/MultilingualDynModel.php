<?php

namespace Alnv\ContaoCatalogManagerBundle\Models;

use Terminal42\DcMultilingualBundle\Model\MultilingualTrait;

class MultilingualDynModel extends DynModel
{
    use MultilingualTrait {
        buildFindQuery as protected buildMultilingualFindQuery;
    }

    public static $strTable = '';

    protected static function isBaseModel(): bool
    {
        return static::class === self::class;
    }

    protected static function buildIdOrAliasCondition(string $strColumn, $varId): array
    {
        $t = static::getTable();

        if ($GLOBALS['TL_DCA'][$t]['fields']['alias']['eval']['isMultilingualAlias'] ?? false) {
            return ["($t.$strColumn=? OR translation.$strColumn=?)", [$varId, $varId]];
        }

        return ["$t.$strColumn=?", [$varId]];
    }

    protected static function buildFindQuery(array $options)
    {
        $strTable = static::getTable();

        static::assertValidTable($strTable);

        $objQueryBuilder = static::buildMultilingualFindQuery($options);
        $strDistanceSelection = static::buildDistanceSelection($options, $strTable);

        if ($strDistanceSelection !== '') {
            $objQueryBuilder->addSelect($strDistanceSelection);
        }

        return $objQueryBuilder;
    }
}
