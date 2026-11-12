<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessGuard;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\FieldPolicy;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\ManagerConfig;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

final class Fixtures
{
    public static function dca(array $config = []): array
    {
        return [
            'config' => array_merge(['_table' => 'cm_test', 'dataContainer' => 'Contao\DC_Table'], $config),
            'palettes' => [
                '__selector__' => ['addImage'],
                'default' => '{title_legend},title,alias;{meta_legend:hide},category,tags,published,owner,addImage',
            ],
            'subpalettes' => ['addImage' => 'image'],
            'fields' => [
                'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
                'pid' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
                'tstamp' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
                'lid' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
                'title' => ['label' => ['Titel', ''], 'inputType' => 'text', 'eval' => ['mandatory' => true, 'maxlength' => 255], 'sql' => "varchar(255) NOT NULL default ''"],
                'alias' => ['label' => ['Alias', ''], 'inputType' => 'text', 'eval' => ['readonly' => true, 'unique' => true], 'sql' => "varchar(255) NOT NULL default ''"],
                'category' => ['label' => ['Kategorie', ''], 'inputType' => 'select', 'options' => ['a', 'b'], 'sql' => "varchar(32) NOT NULL default ''"],
                'tags' => ['inputType' => 'checkbox', 'options' => ['x', 'y'], 'eval' => ['multiple' => true, 'csv' => ','], 'sql' => 'text NULL'],
                'published' => ['inputType' => 'checkbox', 'sql' => "char(1) NOT NULL default ''"],
                'owner' => ['inputType' => 'text', 'sql' => 'int(10) unsigned NOT NULL default 0'],
                'addImage' => ['inputType' => 'checkbox', 'eval' => ['submitOnChange' => true], 'sql' => "char(1) NOT NULL default ''"],
                'image' => ['inputType' => 'fileTree', 'eval' => ['mandatory' => true], 'sql' => 'binary(16) NULL'],
                'password' => ['inputType' => 'password', 'sql' => "varchar(255) NOT NULL default ''"],
                'secret' => ['inputType' => 'text', 'sql' => 'binary(128) NULL'],
                'notes' => ['inputType' => 'textarea', 'eval' => ['doNotShow' => true], 'sql' => 'text NULL'],
                'virtual' => ['inputType' => 'text'],
                'color' => ['inputType' => 'colorpicker', 'sql' => "varchar(64) NOT NULL default ''"],
                'raw' => ['sql' => "varchar(64) NOT NULL default ''"],
            ],
        ];
    }

    public static function config(array $overrides = []): ManagerConfig
    {
        $args = array_merge([
            'moduleId' => 12,
            'table' => 'cm_test',
            'listFields' => ['title', 'category', 'password', 'virtual', 'id'],
            'formFields' => ['title', 'alias', 'category', 'owner', 'image', 'password', 'id', 'notes', 'color'],
            'readonlyFields' => ['category'],
            'filterFields' => ['title', 'category', 'tags', 'published', 'image', 'id'],
            'operations' => ManagerConfig::OPERATIONS,
            'toggleField' => 'published',
            'ownerField' => 'owner',
        ], $overrides);

        return new ManagerConfig(...$args);
    }

    public static function member(int $id = 7): MemberContext
    {
        return new MemberContext($id, 'max');
    }

    public static function guard(array $configOverrides = [], ?MemberContext $member = null, bool $tableAllowed = true, bool $guest = false): AccessGuard
    {
        return new AccessGuard(
            self::config($configOverrides),
            new FieldPolicy(self::dca()),
            $guest ? null : ($member ?? self::member()),
            $tableAllowed,
        );
    }
}
