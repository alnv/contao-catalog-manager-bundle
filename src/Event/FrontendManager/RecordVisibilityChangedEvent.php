<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

final class RecordVisibilityChangedEvent extends AbstractRecordEvent
{
    public function __construct(
        string $table,
        int $id,
        MemberContext $member,
        int $moduleId,
        private readonly bool $visible,
        private readonly string $field,
        private readonly array $oldRecord,
        private readonly array $newRecord,
    )
    {
        parent::__construct($table, $id, $member, $moduleId);
    }

    /** Neue Sichtbarkeit: true = sichtbar (veröffentlicht), false = ausgeblendet. */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    /** Sichtbarkeitsfeld, z. B. published oder invisible. */
    public function getField(): string
    {
        return $this->field;
    }

    public function getOldRecord(): array
    {
        return $this->oldRecord;
    }

    public function getNewRecord(): array
    {
        return $this->newRecord;
    }
}
