<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

use Contao\Config;
use Contao\FileUpload;
use Contao\StringUtil;
use Contao\System;

final class UploadSettingsResolver
{
    public const DENIED_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'cgi', 'pl', 'py', 'sh', 'exe',
        'html', 'htm', 'xhtml', 'shtml', 'js', 'mjs', 'htaccess', 'htpasswd', 'asp', 'aspx', 'jsp'];

    /**
     * @param string       $defaultFolder     globaler Standard-Upload-Ordner (Pfad relativ zum Projekt)
     * @param list<string> $globalTypes       systemweit erlaubte Endungen (localconfig uploadTypes)
     * @param int          $globalMaxFileSize globale Maximalgröße in Bytes (inkl. PHP-Grenze)
     * @param int          $globalImageWidth  localconfig imageWidth (0 = unbegrenzt)
     * @param int          $globalImageHeight localconfig imageHeight
     * @param bool         $moduleHomeDir     Modul-Option "Ein Home-Verzeichnis festlegen"
     */
    public function __construct(
        private readonly string $defaultFolder,
        private readonly array $globalTypes,
        private readonly int $globalMaxFileSize,
        private readonly int $globalImageWidth = 0,
        private readonly int $globalImageHeight = 0,
        private readonly bool $moduleHomeDir = false,
    )
    {
    }

    public static function fromContao(bool $moduleHomeDir): self
    {
        $container = System::getContainer();

        return new self(
            (string)$container->getParameter('catalog_manager.frontend_manager.upload_folder'),
            self::splitExtensions((string)Config::get('uploadTypes')),
            (int)FileUpload::getMaxUploadSize(),
            (int)Config::get('imageWidth'),
            (int)Config::get('imageHeight'),
            $moduleHomeDir,
        );
    }

    public function resolve(array $eval): UploadSettings
    {
        [$target, $folder, $folderSource] = $this->resolveTarget($eval);

        $fieldTypes = self::splitExtensions($eval['extensions'] ?? '');
        $extensions = [] !== $fieldTypes ? $fieldTypes : $this->globalTypes;

        // Systemweite Liste als Obergrenze (wie bei allen Uploads in Contao)
        if ([] !== $this->globalTypes) {
            $extensions = array_intersect($extensions, $this->globalTypes);
        }

        $extensions = array_values(array_unique(array_diff($extensions, self::DENIED_EXTENSIONS)));

        $fieldMaxKb = (int)($eval['maxFileSize'] ?? 0);
        $maxFileSize = $fieldMaxKb > 0 ? min($fieldMaxKb * 1024, $this->globalMaxFileSize ?: PHP_INT_MAX) : $this->globalMaxFileSize;

        return new UploadSettings(
            target: $target,
            folder: $folder,
            folderSource: $folderSource,
            extensions: $extensions,
            extensionsSource: [] !== $fieldTypes ? UploadSettings::SOURCE_FIELD : UploadSettings::SOURCE_GLOBAL,
            maxFileSize: max(0, $maxFileSize),
            maxFileSizeSource: $fieldMaxKb > 0 ? UploadSettings::SOURCE_FIELD : UploadSettings::SOURCE_GLOBAL,
            maxImageWidth: (int)($eval['maxImageWidth'] ?? $eval['imageWidth'] ?? 0) ?: $this->globalImageWidth,
            maxImageHeight: (int)($eval['maxImageHeight'] ?? $eval['imageHeight'] ?? 0) ?: $this->globalImageHeight,
            multiple: !empty($eval['multiple']),
        );
    }

    /**
     * @return array{0: string, 1: ?string, 2: string}
     */
    private function resolveTarget(array $eval): array
    {
        if ($this->moduleHomeDir) {
            return [UploadSettings::TARGET_HOME, null, UploadSettings::SOURCE_MODULE];
        }

        if (!empty($eval['useHomeDir'])) {
            return [UploadSettings::TARGET_HOME, null, UploadSettings::SOURCE_FIELD];
        }

        $folder = $eval['uploadFolder'] ?? null;

        if (\is_string($folder) && '' !== trim($folder)) {
            return [UploadSettings::TARGET_FOLDER, 16 === \strlen($folder) && !ctype_print($folder) ? StringUtil::binToUuid($folder) : trim($folder), UploadSettings::SOURCE_FIELD];
        }

        return [UploadSettings::TARGET_FOLDER, $this->defaultFolder, UploadSettings::SOURCE_GLOBAL];
    }

    /**
     * @return list<string>
     */
    public static function splitExtensions(mixed $value): array
    {
        $items = \is_array($value) ? $value : explode(',', (string)$value);

        return array_values(array_unique(array_filter(
            array_map(static fn ($ext) => strtolower(ltrim(trim((string)$ext), '.')), $items),
            static fn (string $ext) => (bool)preg_match('/^[a-z0-9]{1,10}$/', $ext)
        )));
    }
}
