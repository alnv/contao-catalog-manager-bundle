<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

/**
 * Zugriff verweigert. Der Code unterscheidet die Ebene des Berechtigungsmodells
 * (für Logs, Tests und später für die HTTP-Statuscodes der AJAX-Endpunkte).
 * Die Meldung ist bewusst neutral und darf dem Benutzer angezeigt werden.
 */
final class AccessDeniedException extends \RuntimeException
{
    public const NOT_AUTHENTICATED = 'not_authenticated';
    public const MODULE = 'module';
    public const TABLE = 'table';
    public const FIELD = 'field';
    public const OPERATION = 'operation';
    public const RECORD = 'record';

    public function __construct(private readonly string $level, string $detail = '')
    {
        parent::__construct(sprintf('Access denied (%s)%s', $level, '' !== $detail ? ': ' . $detail : ''));
    }

    public function getLevel(): string
    {
        return $this->level;
    }
}
