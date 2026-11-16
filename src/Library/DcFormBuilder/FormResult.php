<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

final class FormResult
{
    /**
     * @param list<array{legend: string, label: string, hide: bool, fields: list<array>}> $groups
     * @param array<string, mixed>        $values
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        public readonly string $formId,
        public readonly array $groups,
        public readonly bool $submitted,
        public readonly bool $reloaded,
        public readonly array $values,
        public readonly array $errors,
        public readonly bool $hasSelectors,
    )
    {
    }

    public function isValid(): bool
    {
        return $this->submitted && !$this->reloaded && [] === $this->errors;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }
}
