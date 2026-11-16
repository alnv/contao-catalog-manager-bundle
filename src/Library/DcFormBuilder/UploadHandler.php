<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;

final class UploadHandler
{
    private const SESSION_KEY = 'cm_fm_uploads';

    public function __construct(private readonly string $formId)
    {
    }

    public function getWidgetConfig(UploadSettings $settings): array
    {
        return [
            'storeFile' => true,
            'uploadFolder' => null,
            'useHomeDir' => false,
            'doNotOverwrite' => true,
            'extensions' => implode(',', $settings->extensions),
            'maxlength' => $settings->maxFileSize,
            'maxImageWidth' => $settings->maxImageWidth,
            'maxImageHeight' => $settings->maxImageHeight,
            'multipleFiles' => $settings->multiple,
            'mandatory' => false
        ];
    }

    public static function hasUpload(string $field): bool
    {
        $name = $_FILES[$field]['name'] ?? null;

        if (\is_array($name)) {
            return [] !== array_filter($name, static fn($n) => '' !== (string)$n);
        }

        return null !== $name && '' !== (string)$name;
    }

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function validateFiles(string $field): array
    {
        $names = (array)($_FILES[$field]['name'] ?? []);
        $tmpNames = (array)($_FILES[$field]['tmp_name'] ?? []);
        $errors = [];

        foreach ($names as $i => $name) {
            $name = (string)$name;

            if ('' === $name) {
                continue;
            }

            $parts = explode('.', strtolower(basename($name)));
            $extension = \count($parts) > 1 ? (string)array_pop($parts) : '';
            array_shift($parts); // eigentlicher Dateiname

            foreach ($parts as $inner) {
                if (\in_array($inner, UploadSettingsResolver::DENIED_EXTENSIONS, true)) {
                    $errors[] = sprintf($GLOBALS['TL_LANG']['ERR']['filetype'] ?? 'File type "%s" is not allowed.', $inner);
                    continue 2;
                }
            }

            $tmp = (string)($tmpNames[$i] ?? '');

            if (\in_array($extension, self::IMAGE_EXTENSIONS, true) && is_uploaded_file($tmp) && false === @getimagesize($tmp)) {
                $errors[] = sprintf($GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadInvalidImage'] ?? 'The file "%s" is not a valid image.', basename($name));
            }
        }

        return $errors;
    }

    public function extractUploadedUuids(mixed $widgetValue): array
    {
        if (!\is_array($widgetValue)) {
            return [];
        }

        $files = isset($widgetValue['uuid']) || isset($widgetValue['name']) ? [$widgetValue] : $widgetValue;
        $uuids = [];

        foreach ($files as $file) {
            if (\is_array($file) && !empty($file['uuid']) && \is_string($file['uuid'])) {
                $uuids[] = $file['uuid'];
            }
        }

        return $uuids;
    }

    public function remember(string $field, array $uuids): void
    {
        if ([] === $uuids || null === ($session = $this->getSession())) {
            return;
        }

        $data = $session->get(self::SESSION_KEY, []);
        $data[$this->formId][$field] = array_values(array_unique(array_merge($data[$this->formId][$field] ?? [], $uuids)));
        $session->set(self::SESSION_KEY, $data);
    }

    public function filterRemembered(string $field, mixed $requested): array
    {
        if (!\is_array($requested) || null === ($session = $this->getSession())) {
            return [];
        }

        $remembered = $session->get(self::SESSION_KEY, [])[$this->formId][$field] ?? [];

        return array_values(array_intersect(array_map('strval', $requested), $remembered));
    }

    public function clear(): void
    {
        if (null === ($session = $this->getSession())) {
            return;
        }

        $data = $session->get(self::SESSION_KEY, []);
        unset($data[$this->formId]);
        $session->set(self::SESSION_KEY, $data);
    }

    public static function folderUuid(FilesModel $folder): string
    {
        return StringUtil::binToUuid($folder->uuid);
    }

    private function getSession(): ?\Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        return $request?->hasSession() ? $request->getSession() : null;
    }
}
