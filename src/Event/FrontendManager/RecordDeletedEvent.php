<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

/**
 * Ein Datensatz wurde gelöscht. Der Datensatz existiert nicht mehr; getRecord() liefert die Daten
 * unmittelbar vor dem Löschen. Untergeordnete Datensätze (ctable) werden mitgelöscht, aber nicht
 * einzeln gemeldet.
 */
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
