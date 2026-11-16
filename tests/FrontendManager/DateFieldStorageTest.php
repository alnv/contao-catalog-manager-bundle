<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\DateFieldStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateFieldStorageTest extends TestCase
{
    private string $timezone;

    protected function setUp(): void
    {
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);
    }

    public static function sqlProvider(): iterable
    {
        yield 'Catalog-Standard int' => ['int(11) signed NULL', null];
        yield 'int unsigned' => ["int(10) unsigned NOT NULL default '0'", null];
        yield 'Doctrine integer' => [['type' => 'integer'], null];
        yield 'varchar' => ['varchar(10) NULL', null];
        yield 'ohne sql' => [null, null];
        yield 'DATE-String' => ['date NULL', 'Y-m-d'];
        yield 'DATETIME-String' => ['datetime NULL', 'Y-m-d H:i:s'];
        yield 'TIME-String' => ['time NULL', 'H:i:s'];
        yield 'Doctrine date' => [['type' => 'date', 'notnull' => false], 'Y-m-d'];
        yield 'Doctrine datetime_immutable' => [['type' => 'datetime_immutable'], 'Y-m-d H:i:s'];
    }

    #[DataProvider('sqlProvider')]
    public function testSqlFormat(mixed $sql, ?string $expected): void
    {
        $this->assertSame($expected, DateFieldStorage::getSqlFormat(['sql' => $sql]));
    }

    public function testCallbacksKeepTimestampLikeBackend(): void
    {
        $cfg = ['sql' => 'date NULL', 'save_callback' => [['x', 'y']]];

        $this->assertNull(DateFieldStorage::getSqlFormat($cfg));
        $this->assertSame(1798066800, DateFieldStorage::toStorage(1798066800, $cfg));
        $this->assertSame('2026-12-24', DateFieldStorage::fromStorage('2026-12-24', $cfg));
    }

    public function testTimestampColumnsAreUnchanged(): void
    {
        $cfg = ['sql' => 'int(11) signed NULL'];

        $this->assertSame(1798066800, DateFieldStorage::toStorage(1798066800, $cfg));
        $this->assertSame('1798066800', DateFieldStorage::fromStorage('1798066800', $cfg));
        $this->assertSame('', DateFieldStorage::fromStorage('', $cfg));
    }

    public function testNativeColumnsRoundTripInInstallationTimezone(): void
    {
        $date = ['sql' => 'date NULL'];
        $datim = ['sql' => 'datetime NULL'];
        $time = ['sql' => 'time NULL'];

        $ts = mktime(0, 0, 0, 12, 24, 2026);
        $this->assertSame('2026-12-24', DateFieldStorage::toStorage($ts, $date));
        $this->assertSame($ts, DateFieldStorage::fromStorage('2026-12-24', $date));

        // Sommerzeit-Umstellung: 25.10.2026 02:30 existiert zweimal, 29.03.2026 03:00 direkt nach dem Sprung
        foreach (['2026-10-25 01:30:00', '2026-03-29 03:00:00', '2026-07-01 12:00:00'] as $value) {
            $this->assertSame($value, DateFieldStorage::toStorage(DateFieldStorage::fromStorage($value, $datim), $datim));
        }

        $this->assertSame('23:45:00', DateFieldStorage::toStorage(DateFieldStorage::fromStorage('23:45:00', $time), $time));
        $this->assertSame('00:00:00', DateFieldStorage::toStorage(DateFieldStorage::fromStorage('00:00:00', $time), $time));
    }

    public function testEmptyNativeValues(): void
    {
        $this->assertSame('', DateFieldStorage::fromStorage('0000-00-00', ['sql' => 'date NULL']));
        $this->assertSame('', DateFieldStorage::fromStorage('0000-00-00 00:00:00', ['sql' => 'datetime NULL']));
        $this->assertNull(DateFieldStorage::fromStorage(null, ['sql' => 'date NULL']));
    }
}
