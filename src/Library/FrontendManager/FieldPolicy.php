<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

final class FieldPolicy
{
    public const CONTEXT_LIST = 'list';
    public const CONTEXT_FORM = 'form';
    public const CONTEXT_FILTER = 'filter';
    public const CONTEXT_INTERNAL = 'internal';
    public const CONTEXT_TOGGLE = 'toggle';
    public const CONTEXT_OWNER = 'owner';

    /** Systemfelder: werden automatisch gepflegt und sind nie im Formular bearbeitbar. */
    public const SYSTEM_FIELDS = ['id', 'pid', 'ptable', 'tstamp', 'sorting', 'lid', 'language'];

    /** Felder, die nie (auch nicht in der Liste) ausgegeben werden. */
    public const SECRET_FIELDS = ['password', 'secret', 'backupcodes', 'session', 'activation'];

    /** Zuordnung DCA-inputType → Frontend-Formularfeld (TL_FFL). Erweiterbar über $GLOBALS['CM_FRONTEND_MANAGER']['widgets']. */
    public const DEFAULT_WIDGETS = [
        'text' => 'text',
        'textarea' => 'textarea',
        'select' => 'select',
        'radio' => 'radio',
        'checkbox' => 'checkbox',
        'fileTree' => 'upload',
    ];

    private const FILTER_TYPES = ['text', 'textarea', 'select', 'radio', 'checkbox'];

    /**
     * @param array<string, string> $widgets inputType => TL_FFL-Schlüssel
     */
    public function __construct(
        private readonly array $dca,
        private readonly array $widgets = self::DEFAULT_WIDGETS,
    )
    {
    }

    public static function fromGlobals(array $dca): self
    {
        $widgets = array_merge(self::DEFAULT_WIDGETS, (array)($GLOBALS['CM_FRONTEND_MANAGER']['widgets'] ?? []));

        return new self($dca, array_filter($widgets, static fn ($ffl) => \is_string($ffl) && '' !== $ffl));
    }

    public function isEligible(string $field, string $context): bool
    {
        return null === $this->getReason($field, $context);
    }

    public function getReason(string $field, string $context): ?string
    {
        if (!ManagerConfig::isIdentifier($field)) {
            return 'invalid_name';
        }

        $cfg = $this->dca['fields'][$field] ?? null;

        if (!\is_array($cfg)) {
            return 'unknown_field';
        }

        if (\in_array(strtolower($field), self::SECRET_FIELDS, true) || 'password' === ($cfg['inputType'] ?? null)) {
            return 'secret';
        }

        $hasColumn = isset($cfg['sql']) || 'id' === $field;
        $inputType = (string)($cfg['inputType'] ?? '');
        $eval = $cfg['eval'] ?? [];

        switch ($context) {
            case self::CONTEXT_LIST:
                if (!$hasColumn) {
                    return 'no_column';
                }
                return \in_array($field, ['ptable', 'lid', 'language'], true) ? 'system' : null;

            case self::CONTEXT_FORM:
                if (\in_array($field, self::SYSTEM_FIELDS, true)) {
                    return 'system';
                }
                if (!$hasColumn) {
                    return 'no_column';
                }
                if ('' === $inputType) {
                    return 'no_widget';
                }
                if (!isset($this->widgets[$inputType])) {
                    return 'unsupported_widget';
                }
                if (!empty($eval['doNotShow'])) {
                    return 'hidden';
                }
                return null;

            case self::CONTEXT_FILTER:
                if (!$hasColumn || \in_array($field, self::SYSTEM_FIELDS, true)) {
                    return 'system';
                }
                return \in_array($inputType, self::FILTER_TYPES, true) ? null : 'unsupported_widget';

            case self::CONTEXT_INTERNAL:
            case self::CONTEXT_OWNER:
                if (!$hasColumn) {
                    return 'no_column';
                }
                return \in_array($field, ['id', 'tstamp', 'sorting', 'lid', 'language', 'ptable'], true) ? 'system' : null;

            case self::CONTEXT_TOGGLE:
                if (!$hasColumn || \in_array($field, self::SYSTEM_FIELDS, true)) {
                    return 'system';
                }
                return ('checkbox' === $inputType && empty($eval['multiple'])) ? null : 'not_a_toggle';
        }

        return 'unknown_context';
    }

    public function filter(array $fields, string $context): array
    {
        return array_values(array_unique(array_filter($fields, fn ($field) => \is_string($field) && $this->isEligible($field, $context))));
    }

    public function getOptions(string $context): array
    {
        $options = [];

        foreach ($this->dca['fields'] ?? [] as $field => $cfg) {
            if (\is_string($field) && $this->isEligible($field, $context)) {
                $options[$field] = $this->getLabel($field) . ' [' . $field . ']';
            }
        }

        return $options;
    }

    public function getLabel(string $field): string
    {
        $label = $this->dca['fields'][$field]['label'] ?? ($GLOBALS['TL_LANG'][$this->dca['config']['_table'] ?? ''][$field] ?? null);

        if (\is_array($label)) {
            $label = $label[0] ?? null;
        }

        return \is_string($label) && '' !== trim(strip_tags($label)) ? trim(strip_tags($label)) : $field;
    }

    public function getWidgetType(string $field): ?string
    {
        return $this->widgets[$this->dca['fields'][$field]['inputType'] ?? ''] ?? null;
    }

    public function getFieldConfig(string $field): array
    {
        return $this->dca['fields'][$field] ?? [];
    }

    public function getUncoveredMandatoryFields(array $covered): array
    {
        $missing = [];

        foreach ($this->dca['fields'] ?? [] as $field => $cfg) {
            if (!empty($cfg['eval']['mandatory']) && !\in_array($field, $covered, true) && empty($cfg['save_callback']) && isset($cfg['inputType'])) {
                $missing[] = (string)$field;
            }
        }

        return $missing;
    }
}
