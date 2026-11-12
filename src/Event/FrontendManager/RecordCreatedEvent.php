<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

/**
 * Ein Datensatz wurde angelegt (Operator "Neu anlegen" oder "Duplizieren").
 */
final class RecordCreatedEvent extends AbstractRecordEvent
{
    public function __construct(
        string $table,
        int $id,
        MemberContext $member,
        int $moduleId,
        private readonly array $record,
        private readonly ?int $sourceId = null,
    )
    {
        parent::__construct($table, $id, $member, $moduleId);
    }

    /** Der neue Datensatz, wie er gespeichert wurde (inkl. Besitzer, interner Werte und Callback-Änderungen). */
    public function getRecord(): array
    {
        return $this->record;
    }

    /** true, wenn der Datensatz durch Duplizieren entstanden ist. */
    public function isCopy(): bool
    {
        return null !== $this->sourceId;
    }

    /** ID des duplizierten Datensatzes (nur bei isCopy()). */
    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }
}
