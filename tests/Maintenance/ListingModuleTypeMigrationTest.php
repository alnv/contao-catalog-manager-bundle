<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Maintenance;

use Alnv\ContaoCatalogManagerBundle\Migration\ListingModuleTypeMigration;
use Alnv\ContaoCatalogManagerBundle\Modules\ListingModule;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class ListingModuleTypeMigrationTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/cm-listing-migration-' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/templates', 0777, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);
    }

    #[DataProvider('templatesWithoutOverride')]
    public function testCustomTemplateWithoutProjectOverride(string $before, string $after): void
    {
        $this->assertSame($after, $this->migration()->resolveCustomTemplate($before));
    }

    public static function templatesWithoutOverride(): iterable
    {
        yield 'leer bleibt leer (Standard ist jetzt mod_listing_table)' => ['', ''];
        yield 'v2-Standard existiert nicht mehr' => ['mod_listing', ''];
        yield 'eigenes Template bleibt' => ['mod_listing_custom', 'mod_listing_custom'];
        yield 'v3-Template bleibt' => ['mod_listing_table', 'mod_listing_table'];
    }

    #[DataProvider('templatesWithOverride')]
    public function testCustomTemplateWithProjectOverride(string $before, string $after): void
    {
        mkdir($this->projectDir . '/templates/mein-theme');
        touch($this->projectDir . '/templates/mein-theme/mod_listing.html5');

        $this->assertSame($after, $this->migration()->resolveCustomTemplate($before));
    }

    public static function templatesWithOverride(): iterable
    {
        yield 'leer: v2 nutzte mod_listing -> Projekt-Template weiter verwenden' => ['', 'mod_listing'];
        yield 'mod_listing bleibt' => ['mod_listing', 'mod_listing'];
        yield 'eigenes Template bleibt' => ['mod_listing_custom', 'mod_listing_custom'];
    }

    public function testDefaultTemplateOfTheModuleExists(): void
    {
        $template = (new \ReflectionClass(ListingModule::class))->getProperty('strTemplate')->getDefaultValue();

        $this->assertSame('mod_listing_table', $template);
        $this->assertFileExists(\dirname(__DIR__, 2) . '/src/Resources/contao/templates/' . $template . '.html5');
    }

    public function testMigrationIsRegistered(): void
    {
        $services = Yaml::parseFile(\dirname(__DIR__, 2) . '/src/Resources/config/services.yml')['services'];

        $this->assertContains('contao.migration', $services[ListingModuleTypeMigration::class]['tags']);
        $this->assertSame('%kernel.project_dir%', $services[ListingModuleTypeMigration::class]['arguments']['$projectDir']);
    }

    private function migration(): ListingModuleTypeMigration
    {
        return new ListingModuleTypeMigration($this->createStub(Connection::class), $this->projectDir);
    }
}
