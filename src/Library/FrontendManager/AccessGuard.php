<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

final class AccessGuard
{
    public function __construct(
        private readonly ManagerConfig $config,
        private readonly FieldPolicy $policy,
        private readonly ?MemberContext $member,
        private readonly bool $tableAllowed,
    )
    {
    }

    public function getMember(): MemberContext
    {
        if (null === $this->member) {
            throw new AccessDeniedException(AccessDeniedException::NOT_AUTHENTICATED);
        }

        return $this->member;
    }

    public function isAuthenticated(): bool
    {
        return null !== $this->member;
    }

    public function assertModuleAccess(): void
    {
        $this->getMember();
    }

    public function assertTableAccess(): void
    {
        if (!$this->tableAllowed || '' === $this->config->table) {
            throw new AccessDeniedException(AccessDeniedException::TABLE, $this->config->table);
        }
    }

    public function assertCanUse(): void
    {
        $this->assertModuleAccess();
        $this->assertTableAccess();
    }

    public function canPerform(string $operation): bool
    {
        try {
            $this->assertOperation($operation);

            return true;
        } catch (AccessDeniedException) {
            return false;
        }
    }

    public function assertOperation(string $operation): void
    {
        $this->assertCanUse();

        if (!$this->config->allows($operation)) {
            throw new AccessDeniedException(AccessDeniedException::OPERATION, $operation);
        }

        switch ($operation) {
            case ManagerConfig::OPERATION_EDIT:
            case ManagerConfig::OPERATION_CREATE:
                if ([] === $this->getEditableFields()) {
                    throw new AccessDeniedException(AccessDeniedException::OPERATION, $operation . ' without editable fields');
                }
                break;

            case ManagerConfig::OPERATION_TOGGLE:
                if (null === $this->getToggleField()) {
                    throw new AccessDeniedException(AccessDeniedException::OPERATION, 'toggle without visibility field');
                }
                break;
        }
    }

    public function getEditableFields(): array
    {
        $fields = $this->policy->filter($this->config->formFields, FieldPolicy::CONTEXT_FORM);
        $locked = array_merge(
            $this->config->readonlyFields,
            array_keys($this->config->internalValues),
            array_filter([$this->config->ownerField])
        );

        $fields = array_filter($fields, fn ($field) => empty($this->policy->getFieldConfig($field)['eval']['readonly']));

        return array_values(array_diff($fields, $locked));
    }

    public function getReadonlyFields(): array
    {
        $configured = array_merge($this->config->readonlyFields, array_intersect($this->config->formFields, array_filter([$this->config->ownerField])));
        $readonly = $this->policy->filter($configured, FieldPolicy::CONTEXT_FORM);

        foreach ($this->policy->filter($this->config->formFields, FieldPolicy::CONTEXT_FORM) as $field) {
            if (!empty($this->policy->getFieldConfig($field)['eval']['readonly'])) {
                $readonly[] = $field;
            }
        }

        return array_values(array_unique(array_diff($readonly, $this->getEditableFields())));
    }

    /** @return list<string> */
    public function getListFields(): array
    {
        return $this->policy->filter($this->config->listFields, FieldPolicy::CONTEXT_LIST);
    }

    /** @return list<string> */
    public function getFilterFields(): array
    {
        return $this->policy->filter($this->config->filterFields, FieldPolicy::CONTEXT_FILTER);
    }

    /** @return list<string> */
    public function getSortableFields(): array
    {
        if (!$this->config->sortable) {
            return [];
        }

        return array_values(array_filter($this->getListFields(), fn ($field) => isset($this->policy->getFieldConfig($field)['sql']) || 'id' === $field));
    }

    public function getToggleField(): ?string
    {
        $field = $this->config->toggleField;

        return null !== $field && $this->policy->isEligible($field, FieldPolicy::CONTEXT_TOGGLE) ? $field : null;
    }

    public function getOwnerField(): ?string
    {
        $field = $this->config->ownerField;

        return null !== $field && $this->policy->isEligible($field, FieldPolicy::CONTEXT_OWNER) ? $field : null;
    }

    public function getInternalValues(): array
    {
        return array_filter(
            $this->config->internalValues,
            fn ($field) => \is_string($field) && $this->policy->isEligible($field, FieldPolicy::CONTEXT_INTERNAL) && $field !== $this->config->ownerField,
            ARRAY_FILTER_USE_KEY
        );
    }

    public function assertFieldsEditable(array $values): void
    {
        $denied = array_diff(array_keys($values), $this->getEditableFields());

        if ([] !== $denied) {
            throw new AccessDeniedException(AccessDeniedException::FIELD, implode(',', $denied));
        }
    }

    public function getConfig(): ManagerConfig
    {
        return $this->config;
    }

    public function getPolicy(): FieldPolicy
    {
        return $this->policy;
    }
}
