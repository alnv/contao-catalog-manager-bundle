<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use Contao\FilesModel;
use Contao\Folder;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Symfony\Component\Filesystem\Path;

class UploadFolderManager
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $uploadPath,
        private readonly string $homeFolder,
        private readonly bool   $createFolders,
    )
    {
    }

    public static function fromContao(): self
    {
        $container = System::getContainer();

        return new self(
            (string)$container->getParameter('kernel.project_dir'),
            (string)$container->getParameter('contao.upload_path'),
            (string)$container->getParameter('catalog_manager.frontend_manager.home_folder'),
            (bool)$container->getParameter('catalog_manager.frontend_manager.create_folders'),
        );
    }

    public function getTargetFolder(UploadSettings $settings, ?MemberContext $member): FilesModel
    {
        if ($settings->usesHomeDir()) {
            if (null === $member || $member->id < 1) {
                throw new UploadFolderException('Home directory requires a logged-in member.');
            }

            return $this->getHomeFolder($member->id);
        }

        return $this->getFolder((string)$settings->folder);
    }

    public function getFolder(string $uuidOrPath): FilesModel
    {
        if (Validator::isUuid($uuidOrPath)) {
            $model = FilesModel::findByUuid($uuidOrPath);

            if (null === $model || 'folder' !== $model->type) {
                throw new UploadFolderException(sprintf('Upload folder "%s" not found.', $uuidOrPath));
            }

            return $this->ensureFolder((string)$model->path);
        }

        return $this->ensureFolder($uuidOrPath);
    }

    public function getHomeFolder(int $memberId): FilesModel
    {
        $member = MemberModel::findById($memberId);

        if (null === $member) {
            throw new UploadFolderException('Member not found.');
        }

        if ($member->assignDir && $member->homeDir && ($assigned = FilesModel::findByUuid($member->homeDir)) && 'folder' === $assigned->type) {
            if (null !== $this->normalizePath((string)$assigned->path)) {
                return $this->ensureFolder((string)$assigned->path);
            }
        }

        $path = $this->getHomePath($memberId);
        $folder = $this->ensureFolder($path);

        if (!$member->assignDir || !$member->homeDir || !FilesModel::findByUuid($member->homeDir)) {
            $member->assignDir = '1';
            $member->homeDir = $folder->uuid;
            $member->tstamp = time();
            $member->save();
        }

        return $folder;
    }

    public function getHomePath(int $memberId): string
    {
        if ($memberId < 1) {
            throw new UploadFolderException('Invalid member id.');
        }

        $base = $this->normalizePath($this->homeFolder) ?? throw new UploadFolderException('Home folder base is outside the upload directory.');

        return $base . '/user_' . $memberId;
    }

    public function normalizePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ('' === $path || str_contains($path, "\0")) {
            return null;
        }

        $root = Path::canonicalize($this->uploadPath);

        if (Path::isAbsolute($path)) {
            if (!Path::isBasePath(Path::canonicalize($this->projectDir), $path)) {
                return null;
            }

            $path = Path::makeRelative($path, $this->projectDir);
        }

        foreach (explode('/', $path) as $segment) {
            if ('..' === $segment || ('' !== $segment && '.' === $segment[0])) {
                return null;
            }
        }

        $path = Path::canonicalize($path);

        if ($path !== $root && !Path::isBasePath($root, $path)) {
            return null;
        }

        $realRoot = realpath($this->projectDir . '/' . $root);
        $existing = $this->projectDir . '/' . $path;

        while (!file_exists($existing) && $existing !== $this->projectDir . '/' . $root) {
            $existing = \dirname($existing);
        }

        $realExisting = realpath($existing);

        if (false === $realRoot || false === $realExisting || ($realExisting !== $realRoot && !str_starts_with($realExisting, $realRoot . '/'))) {
            return null;
        }

        return $path;
    }

    private function ensureFolder(string $path): FilesModel
    {
        $normalized = $this->normalizePath($path) ?? throw new UploadFolderException(sprintf('Upload folder "%s" is outside the upload directory.', $path));
        $absolute = $this->projectDir . '/' . $normalized;

        if (is_file($absolute)) {
            throw new UploadFolderException(sprintf('"%s" is not a folder.', $normalized));
        }

        if (!is_dir($absolute) && !$this->createFolders) {
            throw new UploadFolderException(sprintf('Upload folder "%s" does not exist (create_folders is disabled).', $normalized));
        }

        try {
            $folder = new Folder($normalized);
            $model = $folder->getModel() ?? FilesModel::findByPath($normalized);
        } catch (\Throwable $e) {
            throw new UploadFolderException(sprintf('Upload folder "%s" could not be created.', $normalized), 0, $e);
        }

        if (null === $model) {
            throw new UploadFolderException(sprintf('Upload folder "%s" is not managed by the file manager.', $normalized));
        }

        return $model;
    }

    public static function toUuidString(FilesModel $model): string
    {
        return StringUtil::binToUuid($model->uuid);
    }
}
