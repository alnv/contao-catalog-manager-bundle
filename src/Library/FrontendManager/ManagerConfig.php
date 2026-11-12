<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Contao\StringUtil;

final class ManagerConfig
{
    public const OPERATION_CREATE = 'create';
    public const OPERATION_EDIT = 'edit';
    public const OPERATION_COPY = 'copy';
    public const OPERATION_DELETE = 'delete';
    public const OPERATION_TOGGLE = 'toggle';

    public const OPERATIONS = [
        self::OPERATION_CREATE,
        self::OPERATION_EDIT,
        self::OPERATION_COPY,
        self::OPERATION_DELETE,
        self::OPERATION_TOGGLE,
    ];

    public function __construct(
        public readonly int $moduleId,
        public readonly string $table,
        public readonly array $listFields = [],
        public readonly array $formFields = [],
        public readonly array $readonlyFields = [],
        public readonly array $filterFields = [],
        public readonly array $operations = [],
        public readonly ?string $toggleField = null,
        public readonly bool $toggleReverse = false,
        public readonly ?string $ownerField = null,
        public readonly array $internalValues = [],
        public readonly bool $sortable = false,
        public readonly array $defaultOrder = [],
        public readonly int $perPage = 0,
        public readonly array $restriction = ['type' => '', 'wizard' => '', 'column' => '', 'value' => ''],
        public readonly bool $useHomeDir = false,
    )
    {
    }

    public static function fromModuleRow(array $row): self
    {
        $fieldList = static fn ($value): array => array_values(array_unique(array_filter(
            array_map('strval', StringUtil::deserialize($value, true)),
            static fn (string $field) => self::isIdentifier($field)
        )));

        $operations = array_values(array_intersect(self::OPERATIONS, StringUtil::deserialize($row['cmFmOperations'] ?? '', true)));

        $internalValues = [];
        foreach (StringUtil::deserialize($row['cmFmInternalValues'] ?? '', true) as $pair) {
            $key = trim((string)($pair['key'] ?? ''));
            if (self::isIdentifier($key)) {
                $internalValues[$key] = StringUtil::decodeEntities((string)($pair['value'] ?? ''));
            }
        }

        $defaultOrder = [];
        $order = $row['cmOrder'] ?? '';
        $order = \is_string($order) ? json_decode(StringUtil::decodeEntities($order), true) : null;
        foreach (\is_array($order) ? $order : [] as $item) {
            $field = (string)($item['option'] ?? $item['field'] ?? '');
            $direction = strtoupper((string)($item['option2'] ?? $item['order'] ?? 'ASC'));
            if (self::isIdentifier($field)) {
                $defaultOrder[] = [$field, 'DESC' === $direction ? 'DESC' : 'ASC'];
            }
        }

        $toggle = (string)($row['cmFmToggleField'] ?? '');
        $owner = (string)($row['cmFmOwnerField'] ?? '');

        return new self(
            moduleId: (int)($row['id'] ?? 0),
            table: (string)($row['cmTable'] ?? ''),
            listFields: $fieldList($row['cmFmListFields'] ?? ''),
            formFields: $fieldList($row['cmFmFormFields'] ?? ''),
            readonlyFields: $fieldList($row['cmFmReadonlyFields'] ?? ''),
            filterFields: $fieldList($row['cmFmFilterFields'] ?? ''),
            operations: $operations,
            toggleField: self::isIdentifier($toggle) ? $toggle : null,
            toggleReverse: (bool)($row['cmFmToggleReverse'] ?? false),
            ownerField: self::isIdentifier($owner) ? $owner : null,
            internalValues: $internalValues,
            sortable: (bool)($row['cmFmSortable'] ?? false),
            defaultOrder: $defaultOrder,
            perPage: max(0, (int)($row['cmLimit'] ?? 0)),
            restriction: [
                'type' => !empty($row['cmFilter']) ? (string)($row['cmFilterType'] ?? '') : '',
                'wizard' => (string)($row['cmWizardFilterSettings'] ?? ''),
                'column' => (string)($row['cmColumn'] ?? ''),
                'value' => (string)($row['cmValue'] ?? ''),
            ],
            useHomeDir: (bool)($row['cmFmUseHomeDir'] ?? false),
        );
    }

    public function allows(string $operation): bool
    {
        return \in_array($operation, $this->operations, true);
    }

    public static function isIdentifier(string $value): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_]{1,64}$/', $value);
    }
}
