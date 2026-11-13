<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Multilingual;

use Alnv\ContaoCatalogManagerBundle\DataContainer\CatalogField;
use Alnv\ContaoCatalogManagerBundle\EventListener\MultilingualCatalogListener;
use Alnv\ContaoCatalogManagerBundle\EventListener\MultilingualDataContainerListener;
use Alnv\ContaoCatalogManagerBundle\Helper\Cache;
use Alnv\ContaoCatalogManagerBundle\Helper\MultilingualHelper;
use Alnv\ContaoCatalogManagerBundle\Models\MultilingualDynModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Terminal42\DcMultilingualBundle\Driver;

/**
 * Hooks und DCA-Callbacks der integrierten Mehrsprachigkeit (ohne Datenbank: die Sprachen der
 * Startpunkte werden über den Request-Cache von MultilingualHelper vorgegeben).
 */
final class MultilingualListenerTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::set(MultilingualHelper::CACHE_KEY, ['languages' => ['en', 'de'], 'fallback' => 'de', 'fallbacks' => ['de']]);
        $GLOBALS['CM_MODELS'] = [];
        unset($GLOBALS['TL_DCA']['cm_test']);
    }

    protected function tearDown(): void
    {
        Cache::remove(MultilingualHelper::CACHE_KEY);
        unset($GLOBALS['TL_DCA']['cm_test'], $GLOBALS['CM_MODELS']);
    }

    public function testParseCatalogFieldAllLanguages(): void
    {
        $field = (new MultilingualCatalogListener())->onParseCatalogField(['eval' => []], ['translatableFor' => serialize(['*', 'en'])]);

        $this->assertSame('*', $field['eval']['translatableFor']);
    }

    public function testParseCatalogFieldSelectedLanguages(): void
    {
        $field = (new MultilingualCatalogListener())->onParseCatalogField(['eval' => ['maxlength' => 5]], ['translatableFor' => serialize(['en'])]);

        $this->assertSame(['maxlength' => 5, 'translatableFor' => ['en']], $field['eval']);
    }

    public function testParseCatalogFieldNotTranslatable(): void
    {
        $listener = new MultilingualCatalogListener();

        $this->assertSame(['eval' => []], $listener->onParseCatalogField(['eval' => []], ['translatableFor' => '']));
        $this->assertSame(['eval' => []], $listener->onParseCatalogField(['eval' => []], []));
    }

    public function testParseCatalogRegistersMultilingualModel(): void
    {
        $catalog = (new MultilingualCatalogListener())->onParseCatalog(['table' => 'cm_test', 'dataContainer' => 'Multilingual']);

        $this->assertSame('cm_test', $catalog['_table']);
        $this->assertSame(MultilingualDynModel::class, $GLOBALS['CM_MODELS']['cm_test']);
    }

    public function testParseCatalogIgnoresTableCatalogs(): void
    {
        $catalog = (new MultilingualCatalogListener())->onParseCatalog(['table' => 'cm_test', 'dataContainer' => 'Table']);

        $this->assertSame(['table' => 'cm_test', 'dataContainer' => 'Table'], $catalog);
        $this->assertSame([], $GLOBALS['CM_MODELS']);
    }

    #[DataProvider('multilingualDataContainers')]
    public function testVirtualDataContainerSwitchesToDriver(string $dataContainer): void
    {
        $GLOBALS['TL_DCA']['cm_test'] = ['config' => ['dataContainer' => $dataContainer], 'fields' => ['alias' => ['eval' => []]]];

        (new MultilingualDataContainerListener())->onLoadVirtualDataContainer('cm_test');

        $this->assertSame([
            'dataContainer' => Driver::class,
            '_table' => 'cm_test',
            'langPid' => 'lid',
            'langColumnName' => 'language',
            'fallbackLang' => 'de',
            'languages' => ['en', 'de'],
        ], $GLOBALS['TL_DCA']['cm_test']['config']);
        $this->assertTrue($GLOBALS['TL_DCA']['cm_test']['fields']['alias']['eval']['isMultilingualAlias']);
    }

    public static function multilingualDataContainers(): iterable
    {
        yield 'catalog setting' => ['Multilingual'];
        yield 'driver class' => [Driver::class];
    }

    public function testVirtualDataContainerIgnoresTableCatalogs(): void
    {
        $dca = ['config' => ['dataContainer' => 'Contao\DC_Table'], 'fields' => ['alias' => ['eval' => []]]];
        $GLOBALS['TL_DCA']['cm_test'] = $dca;

        (new MultilingualDataContainerListener())->onLoadVirtualDataContainer('cm_test');
        (new MultilingualDataContainerListener())->onLoadVirtualDataContainer('cm_unknown');

        $this->assertSame($dca, $GLOBALS['TL_DCA']['cm_test']);
        $this->assertArrayNotHasKey('cm_unknown', $GLOBALS['TL_DCA']);
    }

    public function testLoadDataContainerSetsRealTableForStaticMultilingualDca(): void
    {
        $GLOBALS['TL_DCA']['cm_test'] = ['config' => ['dataContainer' => 'Multilingual']];
        (new MultilingualDataContainerListener())->onLoadDataContainer('cm_test');
        $this->assertSame('cm_test', $GLOBALS['TL_DCA']['cm_test']['config']['_table']);

        $GLOBALS['TL_DCA']['cm_test'] = ['config' => ['dataContainer' => Driver::class]];
        (new MultilingualDataContainerListener())->onLoadDataContainer('cm_test');
        $this->assertArrayNotHasKey('_table', $GLOBALS['TL_DCA']['cm_test']['config']);
    }

    public function testTranslatableForOptions(): void
    {
        $this->assertSame(['*', 'en', 'de'], (new CatalogField())->getLanguages());
    }
}
