<?php

declare(strict_types=1);

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Minimaler PSR-11-Container, über den php-mcp/server die Tool-Instanz bekommt.
 */
final class InstanceContainer implements ContainerInterface
{
    public function __construct(private readonly array $instances)
    {
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new class(sprintf('Service "%s" nicht gefunden.', $id)) extends \RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}
