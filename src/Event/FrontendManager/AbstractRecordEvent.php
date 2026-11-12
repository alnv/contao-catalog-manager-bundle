<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Gemeinsame Daten aller Events der Frontend-Verwaltung.
 *
 * Datensätze werden im Datenbankformat übergeben (wie eine Zeile aus Database::execute()->row()):
 * serialisierte Arrays, Timestamps, Datei-UUIDs binär (StringUtil::binToUuid() bzw.
 * StringUtil::deserialize() zum Umwandeln).
 */
abstract class AbstractRecordEvent extends Event
{
    public function __construct(
        private readonly string $table,
        private readonly int $id,
        private readonly MemberContext $member,
        private readonly int $moduleId,
    )
    {
    }

    /** Tabelle des Datensatzes, z. B. cm_events. */
    public function getTable(): string
    {
        return $this->table;
    }

    /** ID des Datensatzes. */
    public function getId(): int
    {
        return $this->id;
    }

    /** Angemeldetes Mitglied, das die Aktion ausgelöst hat (ID, Benutzername). */
    public function getMember(): MemberContext
    {
        return $this->member;
    }

    /** ID des Frontend-Moduls (tl_module), über das die Aktion lief – z. B. zur Unterscheidung mehrerer Verwaltungen. */
    public function getModuleId(): int
    {
        return $this->moduleId;
    }
}
