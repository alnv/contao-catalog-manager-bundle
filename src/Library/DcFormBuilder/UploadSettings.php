<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

final class UploadSettings
{
    public const TARGET_HOME = 'home';
    public const TARGET_FOLDER = 'folder';

    public const SOURCE_FIELD = 'field';
    public const SOURCE_GLOBAL = 'global';
    public const SOURCE_MODULE = 'module';

    public function __construct(
        public readonly string  $target,
        public readonly ?string $folder,
        public readonly string  $folderSource,
        public readonly array   $extensions,
        public readonly string  $extensionsSource,
        public readonly int     $maxFileSize,
        public readonly string  $maxFileSizeSource,
        public readonly int     $maxImageWidth = 0,
        public readonly int     $maxImageHeight = 0,
        public readonly bool    $multiple = false,
    )
    {
    }

    public function usesHomeDir(): bool
    {
        return self::TARGET_HOME === $this->target;
    }

    public function canUpload(): bool
    {
        return [] !== $this->extensions && $this->maxFileSize > 0;
    }
}
