<?php

namespace Alnv\ContaoCatalogManagerBundle\Event\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use Symfony\Contracts\EventDispatcher\Event;

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

    public function getTable(): string
    {
        return $this->table;
    }

    /** ID des Datensatzes. */
    public function getId(): int
    {
        return $this->id;
    }

    public function getMember(): MemberContext
    {
        return $this->member;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }
}
