<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Maintenance;

use Alnv\ContaoCatalogManagerBundle\Migration\InsertTagCaseMigration;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InsertTagCaseMigrationTest extends TestCase
{
    #[DataProvider('values')]
    public function testNormalize(string $input, string $expected): void
    {
        $migration = new InsertTagCaseMigration($this->createStub(Connection::class));

        $this->assertSame($expected, $migration->normalize($input));
        $this->assertSame(\strlen($input), \strlen($migration->normalize($input)), 'gleiche Länge: serialisierte Werte bleiben gültig');
    }

    public static function values(): iterable
    {
        yield 'active' => ['[{"option3":"{{ACTIVE::topics}}"}]', '[{"option3":"{{active::topics}}"}]'];
        yield 'active_page before active' => ['{{ACTIVE_PAGE::title}}', '{{active_page::title}}'];
        yield 'timestamp with space' => ['{{TIMESTAMP::tstamp::-1 day}}', '{{timestamp::tstamp::-1 day}}'];
        yield 'hyphenated' => ['{{WATCHLIST-COUNT}} {{CM-USER::firstname}} {{LAST-ADDED-MASTER-VIEW-IDS::cm_a}}', '{{watchlist-count}} {{cm-user::firstname}} {{last-added-master-view-ids::cm_a}}'];
        yield 'flags' => ['{{MASTER::headline|strtoupper}}', '{{master::headline|strtoupper}}'];
        yield 'mixed case' => ['{{Master::x}}', '{{master::x}}'];
        yield 'foreign tags untouched' => ['{{LINK_URL::5}} {{TRANSLATE::a}} {{ACTIVEX::a}} {{date::Y}}', '{{LINK_URL::5}} {{TRANSLATE::a}} {{ACTIVEX::a}} {{date::Y}}'];
        yield 'serialized' => ['a:1:{i:0;s:18:"{{ACTIVE::topics}}";}', 'a:1:{i:0;s:18:"{{active::topics}}";}'];
    }
}
