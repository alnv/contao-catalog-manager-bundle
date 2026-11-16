<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\FormResult;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\ManagerConfig;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\TableProvider;
use Alnv\ContaoCatalogManagerBundle\Mcp\PaletteResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigAndTablesTest extends TestCase
{
    public function testFromModuleRowNormalizes(): void
    {
        $config = ManagerConfig::fromModuleRow([
            'id' => '12',
            'cmTable' => 'cm_test',
            'cmFmListFields' => serialize(['title', 'title', 'bad field', 'category']),
            'cmFmFormFields' => serialize(['title']),
            'cmFmOperations' => serialize(['edit', 'drop', 'delete']),
            'cmFmUseHomeDir' => '1',
            'cmFmInternalValues' => serialize([['key' => 'pid', 'value' => '3'], ['key' => 'bad key', 'value' => 'x']]),
            'cmFmOwnerField' => 'owner`',
            'cmFmToggleField' => 'published',
            'cmOrder' => '[{"option":"title","option2":"desc"},{"option":"x;y","option2":"asc"}]',
            'cmFilter' => '',
            'cmFilterType' => 'expert',
            'cmLimit' => '-5',
        ]);

        $this->assertSame(12, $config->moduleId);
        $this->assertSame(['title', 'category'], $config->listFields);
        $this->assertSame(['edit', 'delete'], $config->operations);
        $this->assertTrue($config->useHomeDir);
        $this->assertSame(['pid' => '3'], $config->internalValues);
        $this->assertNull($config->ownerField, 'ungültiger Bezeichner wird verworfen');
        $this->assertSame('published', $config->toggleField);
        $this->assertSame([['title', 'DESC']], $config->defaultOrder);
        $this->assertSame('', $config->restriction['type'], 'Filtertyp nur bei aktivem cmFilter');
        $this->assertSame(0, $config->perPage);
        $this->assertTrue($config->allows('edit'));
        $this->assertFalse($config->allows('drop'));
    }

    public static function tables(): iterable
    {
        yield ['tl_member', true];
        yield ['tl_user', true];
        yield ['tl_page', true];
        yield ['tl_catalog_field', true];
        yield ['tl_user_group', true];
        yield ['TL_MEMBER', true];
        yield ['cm_jobs; DROP TABLE x', true];
        yield ['', true];
        yield ['cm_jobs', false];
        yield ['tl_news', false];
    }

    #[DataProvider('tables')]
    public function testDeniedTables(string $table, bool $denied): void
    {
        $this->assertSame($denied, TableProvider::isDeniedTable($table));
    }

    public function testFormResult(): void
    {
        $this->assertTrue((new FormResult('f', [], true, false, ['a' => 1], [], false))->isValid());
        $this->assertFalse((new FormResult('f', [], true, true, [], [], true))->isValid(), 'Palettenwechsel speichert nicht');
        $this->assertFalse((new FormResult('f', [], true, false, [], ['a' => ['x']], false))->isValid());
        $this->assertFalse((new FormResult('f', [], false, false, [], [], false))->isValid());
    }

    public function testPaletteGroupsWithSubpalette(): void
    {
        $dca = Fixtures::dca();

        $without = PaletteResolver::resolveGroups($dca, ['addImage' => '']);
        $with = PaletteResolver::resolveGroups($dca, ['addImage' => '1']);

        $this->assertSame(['title_legend', 'meta_legend'], array_column($without['groups'], 'legend'));
        $this->assertSame([false, true], array_column($without['groups'], 'hide'));
        $this->assertNotContains('image', $without['groups'][1]['fields']);
        $this->assertSame(['category', 'tags', 'published', 'owner', 'addImage', 'image'], $with['groups'][1]['fields']);
    }
}
