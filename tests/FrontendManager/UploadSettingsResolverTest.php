<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettings;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettingsResolver;
use PHPUnit\Framework\TestCase;

final class UploadSettingsResolverTest extends TestCase
{
    private const GLOBAL_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'svg', 'docx', 'php'];
    private const UUID = 'b3c1a8e2-1d4f-11ef-9a2b-0242ac120002';

    public function testFieldSettingsHavePriority(): void
    {
        $settings = $this->resolver()->resolve([
            'uploadFolder' => self::UUID,
            'extensions' => 'JPG, png,.pdf',
            'maxFileSize' => 512,
            'imageWidth' => '1200',
            'imageHeight' => '',
            'multiple' => true,
        ]);

        $this->assertSame(UploadSettings::TARGET_FOLDER, $settings->target);
        $this->assertSame(self::UUID, $settings->folder);
        $this->assertSame(UploadSettings::SOURCE_FIELD, $settings->folderSource);
        $this->assertSame(['jpg', 'png', 'pdf'], $settings->extensions);
        $this->assertSame(UploadSettings::SOURCE_FIELD, $settings->extensionsSource);
        $this->assertSame(512 * 1024, $settings->maxFileSize);
        $this->assertSame(UploadSettings::SOURCE_FIELD, $settings->maxFileSizeSource);
        $this->assertSame(1200, $settings->maxImageWidth);
        $this->assertSame(800, $settings->maxImageHeight, 'leere Feldangabe → global');
        $this->assertTrue($settings->multiple);
    }

    public function testGlobalSettingsAreTheFallback(): void
    {
        $settings = $this->resolver()->resolve([]);

        $this->assertSame('files/catalog-manager/uploads', $settings->folder);
        $this->assertSame(UploadSettings::SOURCE_GLOBAL, $settings->folderSource);
        $this->assertSame(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'svg', 'docx'], $settings->extensions, 'php ist nie erlaubt');
        $this->assertSame(UploadSettings::SOURCE_GLOBAL, $settings->extensionsSource);
        $this->assertSame(2048000, $settings->maxFileSize);
        $this->assertSame(UploadSettings::SOURCE_GLOBAL, $settings->maxFileSizeSource);
        $this->assertSame(1600, $settings->maxImageWidth);
    }

    public function testFieldCannotExceedGlobalLimits(): void
    {
        $settings = $this->resolver()->resolve(['extensions' => 'jpg,exe,html,xlsx', 'maxFileSize' => 999999]);

        $this->assertSame(['jpg'], $settings->extensions, 'nur systemweit erlaubte, nie ausführbare Typen');
        $this->assertSame(2048000, $settings->maxFileSize, 'globale Grenze (inkl. PHP) gilt immer');
    }

    public function testNoUsableTypeDisablesUploads(): void
    {
        $this->assertFalse($this->resolver()->resolve(['extensions' => 'exe,xlsx'])->canUpload());
        $this->assertFalse($this->resolver(globalMax: 0)->resolve([])->canUpload());
    }

    public function testHomeDirPriority(): void
    {
        // 1. Modul-Option schlägt den Ordner des Feldes
        $module = $this->resolver(moduleHome: true)->resolve(['uploadFolder' => self::UUID]);
        $this->assertTrue($module->usesHomeDir());
        $this->assertNull($module->folder);
        $this->assertSame(UploadSettings::SOURCE_MODULE, $module->folderSource);

        // Feld-Option des Catalog Managers ("Home-Verzeichnis verwenden")
        $field = $this->resolver()->resolve(['useHomeDir' => '1', 'uploadFolder' => self::UUID]);
        $this->assertTrue($field->usesHomeDir());
        $this->assertSame(UploadSettings::SOURCE_FIELD, $field->folderSource);

        // 2. ohne Home: Ordner des Feldes, 3. sonst global
        $this->assertSame(self::UUID, $this->resolver()->resolve(['useHomeDir' => '', 'uploadFolder' => self::UUID])->folder);
        $this->assertSame('files/catalog-manager/uploads', $this->resolver()->resolve(['useHomeDir' => ''])->folder);
    }

    public function testBinaryUuidFromDcaIsConverted(): void
    {
        $binary = hex2bin(str_replace('-', '', self::UUID));

        $this->assertSame(self::UUID, $this->resolver()->resolve(['uploadFolder' => $binary])->folder);
    }

    public function testSplitExtensions(): void
    {
        $this->assertSame(['jpg', 'png'], UploadSettingsResolver::splitExtensions(' .JPG, png ,, jp g, ../x'));
        $this->assertSame(['pdf'], UploadSettingsResolver::splitExtensions(['PDF', '']));
    }

    private function resolver(bool $moduleHome = false, int $globalMax = 2048000): UploadSettingsResolver
    {
        return new UploadSettingsResolver('files/catalog-manager/uploads', self::GLOBAL_TYPES, $globalMax, 1600, 800, $moduleHome);
    }
}
