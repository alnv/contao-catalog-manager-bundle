<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderException;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pfadprüfung und Home-Pfade (ohne Datenbank, mit temporärem Projektverzeichnis).
 */
final class UploadFolderManagerTest extends TestCase
{
    private static string $project;

    public static function setUpBeforeClass(): void
    {
        self::$project = sys_get_temp_dir() . '/cmfm_upload_' . bin2hex(random_bytes(4));
        mkdir(self::$project . '/files/uploads', 0777, true);
        mkdir(self::$project . '/config', 0777, true);
        mkdir(self::$project . '/outside', 0777, true);
        symlink(self::$project . '/outside', self::$project . '/files/link-out');
    }

    public static function tearDownAfterClass(): void
    {
        exec('rm -rf ' . escapeshellarg(self::$project));
    }

    public static function paths(): iterable
    {
        yield 'Upload-Verzeichnis selbst' => ['files', 'files'];
        yield 'vorhandener Unterordner' => ['files/uploads', 'files/uploads'];
        yield 'noch nicht vorhandener Ordner' => ['files/catalog-manager/members/user_7', 'files/catalog-manager/members/user_7'];
        yield 'doppelte Schrägstriche' => ['files//uploads/', 'files/uploads'];
        yield 'Backslashes' => ['files\\uploads', 'files/uploads'];
        yield 'Ausbruch mit ..' => ['files/../config', null];
        yield 'versteckter Ausbruch' => ['files/uploads/../../config', null];
        yield 'außerhalb' => ['config', null];
        yield 'Präfix ohne Trenner' => ['files-private', null];
        yield 'verstecktes Segment' => ['files/.git', null];
        yield '.public' => ['files/uploads/.public', null];
        yield 'Symlink nach außen' => ['files/link-out', null];
        yield 'Unterordner eines Symlinks nach außen' => ['files/link-out/x', null];
        yield 'Nullbyte' => ["files/uploads\0/x", null];
        yield 'leer' => ['', null];
        yield 'absolut außerhalb' => ['/etc', null];
    }

    #[DataProvider('paths')]
    public function testNormalizePath(string $input, ?string $expected): void
    {
        $this->assertSame($expected, $this->manager()->normalizePath($input));
    }

    public function testAbsolutePathInsideProjectIsAccepted(): void
    {
        $this->assertSame('files/uploads', $this->manager()->normalizePath(self::$project . '/files/uploads'));
    }

    public function testHomePathContainsOnlyTheMemberId(): void
    {
        $manager = $this->manager();

        $this->assertSame('files/catalog-manager/members/user_7', $manager->getHomePath(7));
        $this->assertNotSame($manager->getHomePath(7), $manager->getHomePath(8), 'eigenes Verzeichnis pro Mitglied');
        $this->assertFalse(str_starts_with($manager->getHomePath(1), $manager->getHomePath(12)) || str_starts_with($manager->getHomePath(12), $manager->getHomePath(1) . '/'), 'kein Home-Verzeichnis liegt in einem anderen');
    }

    public function testInvalidMemberId(): void
    {
        $this->expectException(UploadFolderException::class);
        $this->manager()->getHomePath(0);
    }

    public function testHomeBaseOutsideUploadDirectoryIsRejected(): void
    {
        $this->expectException(UploadFolderException::class);
        (new UploadFolderManager(self::$project, 'files', 'config/members', true))->getHomePath(7);
    }

    private function manager(): UploadFolderManager
    {
        return new UploadFolderManager(self::$project, 'files', 'files/catalog-manager/members', true);
    }
}
