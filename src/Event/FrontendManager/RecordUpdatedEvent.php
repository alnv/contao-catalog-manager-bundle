<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;

final class RecordUpdatedEvent extends AbstractRecordEvent
{
    /** Felder, die bei jeder Speicherung automatisch wechseln und nicht als Änderung zählen. */
    private const IGNORED_FIELDS = ['tstamp'];

    public function __construct(
        string $table,
        int $id,
        MemberContext $member,
        int $moduleId,
        private readonly array $oldRecord,
        private readonly array $newRecord,
        private readonly array $submittedFields = [],
    )
    {
        parent::__construct($table, $id, $member, $moduleId);
    }

    /** Datensatz vor dem Speichern. */
    public function getOldRecord(): array
    {
        return $this->oldRecord;
    }

    /** Datensatz nach dem Speichern (inkl. Änderungen durch save_callback/onsubmit_callback). */
    public function getNewRecord(): array
    {
        return $this->newRecord;
    }

    /** Felder, die das Mitglied über das Formular übermittelt hat (die bearbeitbaren Felder der Palette). */
    public function getSubmittedFields(): array
    {
        return $this->submittedFields;
    }

    /**
     * Tatsächlich geänderte Felder (ohne tstamp), auch solche, die Callbacks gesetzt haben.
     *
     * @return list<string>
     */
    public function getChangedFields(): array
    {
        return array_keys($this->getChanges());
    }

    /**
     * Änderungen je Feld.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getChanges(): array
    {
        $changes = [];

        foreach (array_unique(array_merge(array_keys($this->oldRecord), array_keys($this->newRecord))) as $field) {
            if (\in_array($field, self::IGNORED_FIELDS, true)) {
                continue;
            }

            $old = $this->oldRecord[$field] ?? null;
            $new = $this->newRecord[$field] ?? null;

            // Datenbankwerte kommen als String; 5 und "5" sind keine Änderung
            if ((null === $old ? null : (string)$old) !== (null === $new ? null : (string)$new)) {
                $changes[(string)$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    public function hasChanges(): bool
    {
        return [] !== $this->getChanges();
    }

    /** true, wenn das Feld geändert wurde. */
    public function isChanged(string $field): bool
    {
        return \array_key_exists($field, $this->getChanges());
    }
}
