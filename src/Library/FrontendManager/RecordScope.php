<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Alnv\ContaoWidgetCollectionBundle\Helpers\Toolkit as WidgetToolkit;
use Contao\StringUtil;

final class RecordScope
{
    private \Closure $replaceInsertTags;

    public function __construct(
        private readonly string        $table,
        private readonly array         $dca,
        private readonly ManagerConfig $config,
        private readonly ?string       $ownerField,
        private readonly MemberContext $member,
        ?\Closure                      $replaceInsertTags = null,
    )
    {
        if (!ManagerConfig::isIdentifier($table)) {
            throw new AccessDeniedException(AccessDeniedException::TABLE, 'invalid table name');
        }

        $this->replaceInsertTags = $replaceInsertTags ?? static fn(string $value): string => (string)Toolkit::replaceInsertTags($value, false);
    }

    /**
     * @return array{columns: list<string>, values: list<mixed>}
     */
    public function getConditions(): array
    {
        $columns = [];
        $values = [];
        $t = $this->quote($this->table);

        if (null !== $this->ownerField) {
            if ($this->member->id < 1) {
                throw new AccessDeniedException(AccessDeniedException::RECORD, 'no member id');
            }

            $columns[] = $t . '.' . $this->quote($this->ownerField) . '=?';
            $values[] = $this->member->id;
        }

        if ($this->isMultilingual()) {
            $columns[] = $t . '.' . $this->quote('lid') . '=0';
        }

        $restriction = $this->getRestrictionConditions();
        array_push($columns, ...$restriction['columns']);
        array_push($values, ...$restriction['values']);

        // Ohne Besitzerfeld und ohne Filter wäre jeder Datensatz der Tabelle erreichbar
        if ([] === $restriction['columns'] && null === $this->ownerField) {
            throw new AccessDeniedException(AccessDeniedException::RECORD, 'no record restriction configured');
        }

        return ['columns' => $columns, 'values' => $values];
    }

    public function getUserFilterConditions(array $input, array $fields): array
    {
        $columns = [];
        $values = [];
        $active = [];
        $t = $this->quote($this->table);

        foreach ($fields as $field) {
            $raw = $input[$field] ?? null;

            if (!\is_scalar($raw)) {
                continue;
            }

            $value = trim((string)$raw);

            if ('' === $value || mb_strlen($value) > 255) {
                continue;
            }

            $cfg = $this->dca['fields'][$field] ?? [];
            $eval = $cfg['eval'] ?? [];
            $column = $t . '.' . $this->quote($field);
            $active[$field] = $value;

            switch ($cfg['inputType'] ?? '') {
                case 'checkbox':
                    if (empty($eval['multiple'])) {
                        if ('1' === $value) {
                            $columns[] = $column . "='1'";
                        } else {
                            $columns[] = '(' . $column . "='' OR " . $column . "='0' OR " . $column . ' IS NULL)';
                        }
                        break;
                    }
                // no break: Mehrfachauswahl wie select multiple
                case 'select':
                case 'radio':
                    if (!empty($eval['multiple'])) {
                        if (isset($eval['csv'])) {
                            $columns[] = 'FIND_IN_SET(?, ' . $column . ')';
                            $values[] = $value;
                        } else {
                            $columns[] = $column . ' LIKE ?';
                            $values[] = '%"' . addcslashes($value, '%_\\') . '"%';
                        }
                    } else {
                        $columns[] = $column . '=?';
                        $values[] = $value;
                    }
                    break;

                default:
                    $columns[] = 'LOWER(CAST(' . $column . ' AS CHAR)) LIKE LOWER(?)';
                    $values[] = '%' . addcslashes($value, '%_\\') . '%';
            }
        }

        return ['columns' => $columns, 'values' => $values, 'active' => $active];
    }

    private function getRestrictionConditions(): array
    {
        $empty = ['columns' => [], 'values' => []];

        switch ($this->config->restriction['type'] ?? '') {
            case 'expert':
                return $this->getExpertConditions();

            case 'wizard':
                return $this->getWizardConditions();
        }

        return $empty;
    }

    private function getExpertConditions(): array
    {
        $columnSource = trim($this->config->restriction['column'] ?? '');
        $valueSource = (string)($this->config->restriction['value'] ?? '');

        if ('' === $columnSource) {
            throw new AccessDeniedException(AccessDeniedException::RECORD, 'empty expert filter');
        }

        $columns = array_values(array_filter(array_map('trim', explode(';', StringUtil::decodeEntities($columnSource))), static fn($c) => '' !== $c));
        $rawValues = '' !== trim($valueSource) ? explode(';', StringUtil::decodeEntities($valueSource)) : [];
        $values = [];

        foreach ($columns as $index => &$column) {
            if (substr_count($column, '?') > 1) {
                throw new AccessDeniedException(AccessDeniedException::RECORD, 'expert filter condition with more than one placeholder');
            }

            $bound = [];
            $column = (string)preg_replace_callback('/\{\{[^{}]+\}\}|\?/', function (array $match) use (&$bound, $rawValues, $index): string {
                $value = '?' === $match[0]
                    ? ($this->replaceInsertTags)((string)($rawValues[$index] ?? ''))
                    : ($this->replaceInsertTags)($match[0]);

                if ('' === trim($value)) {
                    throw new AccessDeniedException(AccessDeniedException::RECORD, 'expert filter value resolves empty');
                }

                $bound[] = $value;

                return '?';
            }, $column);

            array_push($values, ...$bound);
        }
        unset($column);

        return ['columns' => $columns, 'values' => $values];
    }

    private function getWizardConditions(): array
    {
        $source = StringUtil::decodeEntities((string)($this->config->restriction['wizard'] ?? ''));
        $rows = WidgetToolkit::decodeJson($source, ['option' => 'field', 'option2' => 'operator', 'option3' => 'value', 'option4' => 'group']);
        $operators = $GLOBALS['CM_OPERATORS'] ?? [];
        $hasCondition = false;

        foreach ($rows as $row) {
            $operator = $operators[$row['operator'] ?? ''] ?? null;

            if (!\is_array($operator) || empty($operator['token'])) {
                continue;
            }

            if (!ManagerConfig::isIdentifier((string)($row['field'] ?? ''))) {
                throw new AccessDeniedException(AccessDeniedException::RECORD, 'invalid wizard filter field');
            }

            $hasCondition = true;

            if (!empty($operator['empty'])) {
                continue;
            }

            if ('' === trim(($this->replaceInsertTags)((string)($row['value'] ?? '')))) {
                throw new AccessDeniedException(AccessDeniedException::RECORD, 'wizard filter value resolves empty');
            }
        }

        if (!$hasCondition) {
            throw new AccessDeniedException(AccessDeniedException::RECORD, 'empty wizard filter');
        }

        $result = Toolkit::convertComboWizardToModelValues($source, $this->dca['config']['_table'] ?? $this->table);

        if ([] === ($result['column'] ?? [])) {
            throw new AccessDeniedException(AccessDeniedException::RECORD, 'wizard filter produced no condition');
        }

        return ['columns' => array_values($result['column']), 'values' => array_values($result['value'] ?? [])];
    }

    private function isMultilingual(): bool
    {
        $driver = (string)($this->dca['config']['dataContainer'] ?? '');

        return isset($this->dca['fields']['lid']) && (str_contains($driver, 'Multilingual') || str_ends_with($driver, '\\Driver'));
    }

    private function quote(string $identifier): string
    {
        if (!ManagerConfig::isIdentifier($identifier)) {
            throw new AccessDeniedException(AccessDeniedException::FIELD, 'invalid identifier');
        }

        return '`' . $identifier . '`';
    }
}
