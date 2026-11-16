<?php

namespace Alnv\ContaoCatalogManagerBundle\DataContainer;

use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadFolderManager;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettings;
use Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder\UploadSettingsResolver;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessGuard;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\FieldPolicy;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\ManagerConfig;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\TableProvider;
use Alnv\ContaoCatalogManagerBundle\Modules\FrontendManagerModule;
use Contao\DataContainer;
use Contao\Database;
use Contao\FilesModel;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;

/**
 * Backend-Konfiguration der Frontend-Verwaltung (tl_module, Typ catalog-frontend-manager).
 *
 * Die Optionen werden dynamisch aus dem DCA der gewählten Tabelle ermittelt und nutzen dieselbe
 * FieldPolicy wie das Frontend. Die save_callbacks entfernen unzulässige Werte bereits beim
 * Speichern; durchgesetzt werden die Regeln trotzdem bei jedem Frontend-Request erneut.
 */
class FrontendManager
{
    private ?TableProvider $tables = null;

    /**
     * onload_callback: Tabellenauswahl für diesen Modultyp auf freigegebene Tabellen beschränken.
     */
    public function onLoad(?DataContainer $dc = null): void
    {
        if (null === $dc || !$dc->id) {
            return;
        }

        $type = Database::getInstance()->prepare('SELECT type FROM tl_module WHERE id=?')->limit(1)->execute($dc->id)->type;

        if (FrontendManagerModule::TYPE === $type) {
            $GLOBALS['TL_DCA']['tl_module']['fields']['cmTable']['options_callback'] = [self::class, 'getTables'];
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getTables(): array
    {
        $groups = $this->getTableProvider()->getAvailableTables();
        $labels = $GLOBALS['TL_LANG']['tl_module']['cmFmTableGroups'] ?? [];
        $options = [];

        foreach ($groups as $group => $tables) {
            $options[$labels[$group] ?? $group] = $tables;
        }

        return $options;
    }

    public function getListFields(?DataContainer $dc = null): array
    {
        return $this->getOptions($dc, FieldPolicy::CONTEXT_LIST);
    }

    public function getFormFields(?DataContainer $dc = null): array
    {
        return $this->getOptions($dc, FieldPolicy::CONTEXT_FORM);
    }

    public function getFilterFields(?DataContainer $dc = null): array
    {
        return $this->getOptions($dc, FieldPolicy::CONTEXT_FILTER);
    }

    public function getToggleFields(?DataContainer $dc = null): array
    {
        return $this->getOptions($dc, FieldPolicy::CONTEXT_TOGGLE);
    }

    public function getOwnerFields(?DataContainer $dc = null): array
    {
        return $this->getOptions($dc, FieldPolicy::CONTEXT_OWNER);
    }

    public function getOperations(): array
    {
        return ManagerConfig::OPERATIONS;
    }

    // save_callbacks: unzulässige Felder entfernen (Reihenfolge bleibt erhalten)

    public function saveListFields(mixed $value, DataContainer $dc): mixed
    {
        return $this->sanitizeFields($value, $dc, FieldPolicy::CONTEXT_LIST);
    }

    public function saveFormFields(mixed $value, DataContainer $dc): mixed
    {
        return $this->sanitizeFields($value, $dc, FieldPolicy::CONTEXT_FORM);
    }

    public function saveFilterFields(mixed $value, DataContainer $dc): mixed
    {
        return $this->sanitizeFields($value, $dc, FieldPolicy::CONTEXT_FILTER);
    }

    /**
     * Interne Werte: Schlüssel müssen existierende, zulässige Spalten sein.
     */
    public function saveInternalValues(mixed $value, DataContainer $dc): mixed
    {
        $policy = $this->getPolicy($dc);

        foreach (StringUtil::deserialize($value, true) as $pair) {
            $key = trim((string)($pair['key'] ?? ''));

            if ('' === $key) {
                continue;
            }

            if (null === $policy || !$policy->isEligible($key, FieldPolicy::CONTEXT_INTERNAL)) {
                throw new \RuntimeException(sprintf($GLOBALS['TL_LANG']['tl_module']['cmFmInvalidInternalField'] ?? 'The field "%s" cannot be set internally.', $key));
            }
        }

        return $value;
    }

    /**
     * onsubmit_callback: Hinweise auf Konfigurationen, mit denen die Verwaltung nicht funktionieren kann.
     */
    public function onSubmit(DataContainer $dc): void
    {
        if (FrontendManagerModule::TYPE !== ($dc->activeRecord->type ?? null)) {
            return;
        }

        // Contao 5: activeRecord ist ein stdClass (früher ein Model mit row())
        $record = $dc->activeRecord;
        $row = \is_object($record) && method_exists($record, 'row') ? $record->row() : (array)$record;

        $config = ManagerConfig::fromModuleRow($row);
        $policy = $this->getPolicy($dc);
        $lang = $GLOBALS['TL_LANG']['tl_module'];

        if (null === $policy) {
            return;
        }

        if (null === $config->ownerField && '' === $config->restriction['type']) {
            Message::addError($lang['cmFmNoRestriction'] ?? 'Without an owner field or a filter no records are accessible.');
        }

        if (null !== $config->ownerField && \in_array($config->ownerField, $config->formFields, true)) {
            Message::addInfo($lang['cmFmOwnerReadonly'] ?? 'The owner field is never editable.');
        }

        if ($config->allows(ManagerConfig::OPERATION_TOGGLE) && null === $config->toggleField) {
            Message::addError($lang['cmFmToggleMissing'] ?? 'Select a visibility field for the toggle operation.');
        }

        // Felder, die bearbeitbar UND Nur-Lese sind: Nur-Lese hat Vorrang
        $overlap = array_values(array_intersect($config->formFields, $config->readonlyFields));
        if ([] !== $overlap) {
            Message::addInfo(sprintf($lang['cmFmReadonlyOverlap'] ?? 'Read-only takes precedence, these fields are not editable: %s', implode(', ', $overlap)));
        }

        $guard = new AccessGuard($config, $policy, null, true);
        if (($config->allows(ManagerConfig::OPERATION_CREATE) || $config->allows(ManagerConfig::OPERATION_EDIT)) && [] === $guard->getEditableFields()) {
            Message::addError($lang['cmFmNoEditableFields'] ?? 'No field is editable: "create" and "edit" are not available.');
        }

        if ($config->allows(ManagerConfig::OPERATION_CREATE) || $config->allows(ManagerConfig::OPERATION_COPY)) {
            $covered = array_merge($config->formFields, array_keys($config->internalValues), array_filter([$config->ownerField]));
            $missing = $policy->getUncoveredMandatoryFields($covered);

            if ([] !== $missing) {
                Message::addInfo(sprintf($lang['cmFmMandatoryMissing'] ?? 'Mandatory fields not editable or set internally: %s', implode(', ', $missing)));
            }

            $ptable = $GLOBALS['TL_DCA'][$config->table]['config']['ptable'] ?? null;
            if ($ptable && !isset($config->internalValues['pid'])) {
                Message::addInfo(sprintf($lang['cmFmParentMissing'] ?? 'New records need a parent record in %s: set "pid" as internal value.', $ptable));
            }
        }

        $this->addUploadTargetInfo($config, $policy, $guard->getEditableFields());
    }

    /**
     * Zeigt pro Dateifeld, wohin Uploads gespeichert werden (gleiche Auflösung wie im Frontend), und
     * warnt bei Zielen außerhalb des Upload-Verzeichnisses. Ordner werden hier nicht angelegt.
     *
     * @param list<string> $editable
     */
    private function addUploadTargetInfo(ManagerConfig $config, FieldPolicy $policy, array $editable): void
    {
        $lang = $GLOBALS['TL_LANG']['tl_module'];
        $uploadFields = array_values(array_filter($editable, static fn ($f) => 'upload' === $policy->getWidgetType($f)));

        if ([] === $uploadFields) {
            return;
        }

        try {
            $resolver = UploadSettingsResolver::fromContao($config->useHomeDir);
            $folders = UploadFolderManager::fromContao();
        } catch (\Throwable) {
            return;
        }

        $targets = [];

        foreach ($uploadFields as $field) {
            $settings = $resolver->resolve($policy->getFieldConfig($field)['eval'] ?? []);

            if ($settings->usesHomeDir()) {
                $targets[] = $field . ' → ' . ($lang['cmFmUploadHome'] ?? 'member home directory');
                continue;
            }

            $path = (string)$settings->folder;

            if (Validator::isUuid($path)) {
                $path = (string)(FilesModel::findByUuid($path)?->path ?? '');
            }

            if ('' === $path || null === $folders->normalizePath($path)) {
                Message::addError(sprintf($lang['cmFmUploadInvalid'] ?? 'Invalid upload folder for "%s".', $field, System::getContainer()->getParameter('contao.upload_path')));
                continue;
            }

            $targets[] = $field . ' → ' . $path . (UploadSettings::SOURCE_GLOBAL === $settings->folderSource ? ' (global)' : '');
        }

        if ([] !== $targets) {
            Message::addInfo(sprintf($lang['cmFmUploadTargets'] ?? 'Upload targets: %s', implode('; ', $targets)));
        }
    }

    private function sanitizeFields(mixed $value, DataContainer $dc, string $context): mixed
    {
        $policy = $this->getPolicy($dc);
        $fields = StringUtil::deserialize($value, true);

        if (null === $policy) {
            return $value;
        }

        $filtered = $policy->filter(array_map('strval', $fields), $context);

        return [] === $filtered ? null : serialize($filtered);
    }

    private function getOptions(?DataContainer $dc, string $context): array
    {
        return $this->getPolicy($dc)?->getOptions($context) ?? [];
    }

    private function getPolicy(?DataContainer $dc): ?FieldPolicy
    {
        $table = (string)($dc?->activeRecord?->cmTable ?? '');

        if ('' === $table || !$this->getTableProvider()->isAllowed($table)) {
            return null;
        }

        return FieldPolicy::fromGlobals($this->getTableProvider()->loadDca($table));
    }

    private function getTableProvider(): TableProvider
    {
        return $this->tables ??= new TableProvider();
    }
}
