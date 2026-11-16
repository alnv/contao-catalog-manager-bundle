<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\DateFieldStorage;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\FormResult;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderException;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderManager;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadHandler;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettings;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettingsResolver;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\FieldPolicy;
use Alnv\ContaoCatalogManagerBundle\Mcp\HeadlessDataContainer;
use Alnv\ContaoCatalogManagerBundle\Mcp\PaletteResolver;
use Alnv\ContaoCatalogManagerBundle\Mcp\RecordStub;
use Alnv\ContaoCatalogManagerBundle\Widgets\FormDateTime;
use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Database;
use Contao\Date;
use Contao\FilesModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\Widget;

class DcFormBuilder
{
    public const RELOAD_FIELD = '_cmfm_reload';

    protected FieldPolicy $policy;

    protected UploadHandler $uploads;

    protected ?UploadSettingsResolver $uploadResolver;

    protected ?UploadFolderManager $uploadFolders;

    public function __construct(
        protected readonly string $table,
        protected readonly array  $dca,
        protected readonly array  $editableFields,
        protected readonly array  $readonlyFields = [],
        protected readonly array  $options = [],
    )
    {
        $this->policy = FieldPolicy::fromGlobals($dca);
        $this->uploads = new UploadHandler($this->getFormId());
        $this->uploadResolver = $options['uploadResolver'] ?? null;
        $this->uploadFolders = $options['uploadFolders'] ?? null;
    }

    public function getFormId(): string
    {
        return (string)($this->options['formId'] ?? ('cm_dc_form_' . $this->table));
    }

    public function build(array $record, bool $submitted): FormResult
    {
        $reload = $submitted && null !== Input::post(self::RELOAD_FIELD);
        $paletteValues = $record;

        // Bei Übermittlung bestimmen die übermittelten Selektorwerte die Palette (nur bearbeitbare Felder)
        if ($submitted) {
            foreach ($this->dca['palettes']['__selector__'] ?? [] as $selector) {
                if (\in_array($selector, $this->editableFields, true)) {
                    $posted = Input::post($selector);
                    $paletteValues[$selector] = \is_array($posted) ? ($posted[0] ?? '') : (string)$posted;
                }
            }
        }

        $dc = new HeadlessDataContainer($this->table, $this->options['recordId'] ?? null, new RecordStub($record));
        $allowed = array_merge($this->editableFields, $this->readonlyFields);
        $groups = [];
        $values = [];
        $errors = [];
        $hasSelectors = false;

        foreach (PaletteResolver::resolveGroups($this->dca, $paletteValues)['groups'] as $group) {
            $fields = [];

            foreach ($group['fields'] as $field) {
                if (!\in_array($field, $allowed, true) || !$this->policy->isEligible($field, FieldPolicy::CONTEXT_FORM)) {
                    continue;
                }

                $editable = \in_array($field, $this->editableFields, true);
                $isSelector = $editable && \in_array($field, $this->dca['palettes']['__selector__'] ?? [], true);
                $hasSelectors = $hasSelectors || $isSelector;

                $item = $this->buildField($field, $record, $editable, $isSelector, $submitted && !$reload, $dc);

                if (null === $item) {
                    continue;
                }

                if ($item['errors']) {
                    $errors[$field] = $item['errors'];
                } elseif ($editable && $submitted && !$reload && \array_key_exists('value', $item)) {
                    $values[$field] = $item['value'];
                }

                unset($item['value']);
                $fields[] = $item;
            }

            if ([] !== $fields) {
                $groups[] = [
                    'legend' => $group['legend'],
                    'label' => $this->getLegendLabel($group['legend']),
                    'hide' => $group['hide'],
                    'fields' => $fields,
                ];
            }
        }

        return new FormResult($this->getFormId(), $groups, $submitted, $reload, $errors ? [] : $values, $errors, $hasSelectors);
    }

    public function finish(): void
    {
        $this->uploads->clear();
    }

    /**
     * @return array{name: string, type: string, label: string, html: string, readonly: bool, selector: bool, files: list<array>, errors: list<string>, value?: mixed}|null
     */
    protected function buildField(string $field, array $record, bool $editable, bool $isSelector, bool $validate, HeadlessDataContainer $dc): ?array
    {
        $ffl = $this->policy->getWidgetType($field);
        $class = $GLOBALS['TL_FFL'][$ffl] ?? null;

        if (null === $ffl || !\is_string($class) || !class_exists($class)) {
            return null;
        }

        $dcaCfg = $this->policy->getFieldConfig($field);
        $cfg = $this->prepareConfig($dcaCfg, $ffl);
        $current = $record[$field] ?? null;
        $isDate = 'text' === $ffl && FormDateTime::supports($cfg['eval']['rgxp'] ?? null);

        if ($isDate) {
            $class = FormDateTime::class;
            $current = DateFieldStorage::fromStorage($this->applyLoadCallbacks($dcaCfg, $current, $dc, $field, $record), $dcaCfg);
        }
        $files = 'upload' === $ffl ? $this->getCurrentFiles($current) : [];
        $uploadSettings = null;
        $uploadBlocked = false;

        if ('upload' === $ffl) {
            $uploadSettings = $this->getUploadSettings($field);

            // Ohne verwendbare Upload-Einstellungen bleibt das Feld sichtbar, ist aber nicht bearbeitbar
            if (null === $uploadSettings || !$uploadSettings->canUpload()) {
                $uploadBlocked = $editable;
                $editable = false;
            } else {
                // Pflicht wird nach dem Zusammenführen mit vorhandenen Dateien selbst geprüft
                $cfg['eval'] = array_merge($cfg['eval'], $this->uploads->getWidgetConfig($uploadSettings));
            }

            $current = null;
        }

        $rte = 'textarea' === $ffl ? $this->getRichTextEditor($field) : null;

        // Wie im Backend: {{file::…}} in src-Attributen für den Editor in Pfade umwandeln
        if (null !== $rte && \is_string($current)) {
            $current = StringUtil::insertTagToSrc(StringUtil::removeBasePath($current));
        }

        $dc->setField($field, StringUtil::deserialize($record[$field] ?? null));
        $attributes = $class::getAttributesFromDca($cfg, $field, $current, $field, $this->table, $dc);
        $attributes['id'] = $this->getFormId() . '_' . $field;

        /** @var Widget $widget */
        $widget = new $class($attributes);

        if (!$editable) {
            $widget->addAttributes(['disabled' => 'disabled']);
        }

        if ($isSelector) {
            $widget->addAttributes(['data-cm-fm-reload' => '1']);
        }

        $item = [
            'name' => $field,
            'type' => $ffl,
            'label' => $this->policy->getLabel($field),
            'readonly' => !$editable,
            'selector' => $isSelector,
            'files' => $files,
            'errors' => [],
        ];

        if ($uploadBlocked || ($editable && null !== $uploadSettings)) {
            $widget->help = StringUtil::specialchars($this->getUploadHint($uploadSettings, !$uploadBlocked));
        }

        if ($editable && $validate && null !== $uploadSettings && UploadHandler::hasUpload($field)) {
            $fileErrors = $this->uploads->validateFiles($field);

            if ([] === $fileErrors) {
                try {
                    $widget->uploadFolder = $this->uploadFolders->getTargetFolder($uploadSettings, $this->options['member'] ?? null)->uuid;
                } catch (UploadFolderException $e) {
                    $this->logError($e);
                    $fileErrors = [$GLOBALS['TL_LANG']['MSC']['cmFrontendManager']['uploadUnavailable'] ?? 'File uploads are currently not possible.'];
                }
            }

            if ([] !== $fileErrors) {
                foreach ($fileErrors as $error) {
                    $widget->addError($error);
                }

                $item['errors'] = $fileErrors;
                $item['html'] = $widget->parse();

                return $item;
            }
        }

        if ($editable && $validate) {
            $widget->validate();

            $value = 'upload' === $ffl
                ? $this->resolveUploadValue($field, $widget, $record[$field] ?? null, $files, $item)
                : $this->normalizeValue($field, $widget->value, $widget);

            // Wie im Backend: Dateipfade in src-Attributen als Insert-Tags speichern
            if (null !== $rte && \is_string($value) && '' !== $value) {
                $value = StringUtil::addBasePath(StringUtil::srcToInsertTag($value));
            }

            if (!$widget->hasErrors()) {
                $this->validateUnique($field, $value, $widget);
            }

            if ($widget->hasErrors()) {
                $item['errors'] = $widget->getErrors();
            } else {
                $item['value'] = $value;
            }
        }

        $item['html'] = $widget->parse();

        if (null !== $rte) {
            $item['html'] .= $this->renderRichTextEditor($rte, 'ctrl_' . $attributes['id'], !$editable);
            $item['rte'] = true;
        }

        return $item;
    }

    protected function applyLoadCallbacks(array $cfg, mixed $value, HeadlessDataContainer $dc, string $field, array $record): mixed
    {
        if (empty($cfg['load_callback']) || !\is_array($cfg['load_callback'])) {
            return $value;
        }

        $dc->setField($field, StringUtil::deserialize($record[$field] ?? null));

        foreach ($cfg['load_callback'] as $callback) {
            try {
                if (\is_array($callback)) {
                    $value = System::importStatic($callback[0])->{$callback[1]}($value, $dc);
                } elseif (\is_callable($callback)) {
                    $value = $callback($value, $dc);
                }
            } catch (\Throwable $e) {
                $this->logError($e);
            }
        }

        return $value;
    }

    protected function getRichTextEditor(string $field): ?string
    {
        $rte = (string)($this->policy->getFieldConfig($field)['eval']['rte'] ?? '');

        return str_starts_with($rte, 'tiny') ? $rte : null;
    }

    protected function renderRichTextEditor(string $rte, string $selector, bool $readonly): string
    {
        [$file, $type] = explode('|', $rte, 2) + [null, null];

        if (!preg_match('/^tiny[A-Za-z0-9_]*$/', (string)$file)) {
            return '';
        }

        $GLOBALS['TL_JAVASCRIPT']['cm-tinymce'] = System::getContainer()->get('assets.packages')->getUrl('js/tinymce.min.js', 'contao-components/tinymce4');

        $template = new BackendTemplate('be_' . $file);
        $template->selector = $selector;
        $template->type = $type;
        $template->fileBrowserTypes = '';
        $template->source = $this->table . '.' . (int)($this->options['recordId'] ?? 0);
        $template->readonly = $readonly;
        $template->theme = Backend::getTheme();
        $template->enableAce = false;
        $template->aceType = '';
        $template->enableTinyMce = (bool)(Config::get('useRTE') ?? true);
        $template->tinyMceLanguage = Backend::getTinyMceLanguage();

        return $template->parse();
    }

    protected function getUploadSettings(string $field): ?UploadSettings
    {
        if (null === $this->uploadResolver || null === $this->uploadFolders) {
            return null;
        }

        return $this->uploadResolver->resolve($this->policy->getFieldConfig($field)['eval'] ?? []);
    }

    protected function getUploadHint(?UploadSettings $settings, bool $usable): string
    {
        $text = $GLOBALS['TL_LANG']['MSC']['cmFrontendManager'] ?? [];

        if (!$usable || null === $settings) {
            return (string)($text['uploadUnavailable'] ?? 'File uploads are currently not possible.');
        }

        return sprintf(
            (string)($text['uploadHint'] ?? 'Allowed file types: %s. Maximum file size: %s.'),
            implode(', ', $settings->extensions),
            System::getReadableSize($settings->maxFileSize)
        );
    }

    protected function logError(\Throwable $e): void
    {
        $container = System::getContainer();

        if ($container->has('monolog.logger.contao.error')) {
            $container->get('monolog.logger.contao.error')->error(sprintf('Catalog frontend form (%s): %s', $this->table, $e->getMessage()), ['exception' => $e]);
        }
    }

    protected function prepareConfig(array $cfg, string $ffl): array
    {
        $eval = $cfg['eval'] ?? [];

        unset($eval['submitOnChange'], $eval['chosen'], $eval['tl_class'], $eval['preserveTags']);

        $rte = (string)($eval['rte'] ?? '');
        $eval['allowHtml'] = 'textarea' === $ffl && str_starts_with($rte, 'tiny');
        unset($eval['rte']);

        if (\in_array($eval['rgxp'] ?? null, FormDateTime::RGXPS, true)) {
            unset($eval['datepicker'], $eval['dateFormat'], $eval['placeholder']);
        }

        $cfg['eval'] = $eval;
        unset($cfg['save_callback'], $cfg['load_callback'], $cfg['wizard'], $cfg['xlabel']);

        return $cfg;
    }

    protected function normalizeValue(string $field, mixed $value, Widget $widget): mixed
    {
        $cfg = $this->policy->getFieldConfig($field);
        $eval = $cfg['eval'] ?? [];

        if ('checkbox' === ($cfg['inputType'] ?? null) && empty($eval['multiple'])) {
            $value = \is_array($value) ? ($value[0] ?? '') : $value;

            return '' !== (string)$value ? '1' : '';
        }

        if (!empty($eval['multiple'])) {
            return array_values(array_filter((array)$value, static fn($v) => '' !== (string)$v));
        }

        if (\in_array($eval['rgxp'] ?? null, ['date', 'time', 'datim'], true)) {
            if (null === $value || '' === (string)$value) {
                return '';
            }

            try {
                return DateFieldStorage::toStorage((int)(new Date((string)$value, Date::getFormatFromRgxp($eval['rgxp'])))->tstamp, $cfg);
            } catch (\Throwable) {
                $widget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'] ?? 'Invalid date "%s".', (string)$value));

                return null;
            }
        }

        return \is_array($value) ? ($value[0] ?? '') : $value;
    }

    protected function resolveUploadValue(string $field, Widget $widget, mixed $stored, array $files, array &$item): mixed
    {
        $eval = $this->policy->getFieldConfig($field)['eval'] ?? [];
        $multiple = !empty($eval['multiple']);

        $new = $this->uploads->extractUploadedUuids($widget->value);
        $this->uploads->remember($field, $new);

        $pending = $this->uploads->filterRemembered($field, Input::post('_cmfm_pending_' . $field));
        $remove = (array)Input::post('_cmfm_remove_' . $field);

        $existing = array_values(array_filter(array_column($files, 'uuid'), static fn($uuid) => !\in_array($uuid, $remove, true)));
        $uuids = array_values(array_unique($multiple ? array_merge($existing, $pending, $new) : array_slice(array_merge($new, $pending, $existing), 0, 1)));

        foreach (array_merge($pending, $new) as $uuid) {
            if ($model = FilesModel::findByUuid($uuid)) {
                $item['files'][] = ['uuid' => $uuid, 'name' => basename($model->path), 'path' => $model->path, 'pending' => true];
            }
        }

        if (!empty($eval['mandatory']) && [] === $uuids) {
            $widget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'] ?? '%s', $this->policy->getLabel($field)));
        }

        return $multiple ? $uuids : ($uuids[0] ?? null);
    }

    /**
     * @return list<array{uuid: string, name: string, path: string, pending: bool}>
     */
    protected function getCurrentFiles(mixed $value): array
    {
        $files = [];

        foreach (StringUtil::deserialize($value, true) as $uuid) {
            if (!\is_string($uuid) || (!Validator::isBinaryUuid($uuid) && !Validator::isStringUuid($uuid))) {
                continue;
            }

            if ($model = FilesModel::findByUuid($uuid)) {
                $files[] = ['uuid' => StringUtil::binToUuid($model->uuid), 'name' => basename($model->path), 'path' => $model->path, 'pending' => false];
            }
        }

        return $files;
    }

    protected function validateUnique(string $field, mixed $value, Widget $widget): void
    {
        $eval = $this->policy->getFieldConfig($field)['eval'] ?? [];

        if (empty($eval['unique']) || null === $value || '' === $value || \is_array($value)) {
            return;
        }

        if (!Database::getInstance()->isUniqueValue($this->table, $field, $value, (int)($this->options['recordId'] ?? 0) ?: null)) {
            $widget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'] ?? 'Duplicate value in %s.', $this->policy->getLabel($field)));
        }
    }

    protected function getLegendLabel(string $legend): string
    {
        if ('' === $legend) {
            return '';
        }

        $label = $GLOBALS['TL_LANG'][$this->table][$legend] ?? $legend;

        return \is_array($label) ? (string)($label[0] ?? $legend) : (string)$label;
    }
}
