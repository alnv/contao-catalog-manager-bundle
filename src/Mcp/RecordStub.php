<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

/**
 * Ersatz für Contao\Database\Result als "activeRecord" in DCA-Callbacks.
 *
 * Die Catalog-Manager-Callbacks greifen auf $dc->activeRecord->feld und
 * $dc->activeRecord->row() zu – beides wird hier abgebildet.
 */
final class RecordStub
{
    public function __construct(private array $data = [])
    {
    }

    public function __get(string $key): mixed
    {
        if ('numRows' === $key) {
            return 1;
        }

        return $this->data[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function row(bool $enumerated = false): array
    {
        return $enumerated ? array_values($this->data) : $this->data;
    }

    public function next(): self|false
    {
        return false;
    }
}
