<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

final class RecordDeletedEvent extends AbstractRecordEvent
{
    public function __construct(
        string $table,
        int $id,
        MemberContext $member,
        int $moduleId,
        private readonly array $record,
    )
    {
        parent::__construct($table, $id, $member, $moduleId);
    }

    /** Datensatz vor dem Löschen. */
    public function getRecord(): array
    {
        return $this->record;
    }
}
