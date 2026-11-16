<?php

declare(strict_types=1);

namespace Alnv\ContaoCatalogManagerBundle\Helper;

use Alnv\ContaoCatalogManagerBundle\Models\CatalogModel;
use Contao\Database;
use Contao\System;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

final class CatalogTableGuard
{
    private const IDENTIFIER_PATTERN = '/^[A-Za-z0-9_]{1,64}$/';

    private static array $catalogTables = [];

    public static function isValidIdentifier(?string $identifier): bool
    {
        return null !== $identifier && 1 === preg_match(self::IDENTIFIER_PATTERN, $identifier);
    }

    public static function isCatalogTable(?string $table): bool
    {
        if (!self::isValidIdentifier($table)) {
            return false;
        }

        if (!isset(self::$catalogTables[$table])) {
            self::$catalogTables[$table] = null !== CatalogModel::findOneBy(['tl_catalog.`table`=?'], [$table]);
        }

        return self::$catalogTables[$table];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function assertCatalogTable(?string $table): string
    {
        if (!self::isCatalogTable($table)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a catalog table.', (string) $table));
        }

        return $table;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function assertValidIdentifier(mixed $identifier, string $label = 'identifier'): string
    {
        if (!\is_string($identifier) || !self::isValidIdentifier($identifier)) {
            throw new \InvalidArgumentException(sprintf('Invalid %s "%s". Allowed are letters, digits and underscores (max. 64 characters).', $label, \is_scalar($identifier) ? (string) $identifier : get_debug_type($identifier)));
        }

        return $identifier;
    }

    /**
     * true, wenn die Tabelle NICHT ausschließlich dem Catalog-Manager gehört und
     * daher nie umbenannt oder gelöscht werden darf:
     *
     * - es gibt eine DCA-Datei (Contao-Core oder andere Erweiterung, z. B. tl_member, tl_user),
     * - die Tabelle gehört zu einer Doctrine-Entity (z. B. tl_cron_job, tl_trusted_device),
     * - ein anderer Katalog nutzt dieselbe Tabelle.
     *
     * Im Zweifel (z. B. ungültiger Name, Fehler beim Prüfen) gilt die Tabelle als geschützt.
     */
    public static function isProtectedTable(?string $table, ?int $exceptCatalogId = null): bool
    {
        if (!self::isValidIdentifier($table)) {
            return true;
        }

        try {
            $container = System::getContainer();

            // 1) DCA-Datei vorhanden?
            try {
                $files = $container->get('contao.resource_locator')->locate('dca/' . $table . '.php', null, false);
                if (!empty($files)) {
                    return true;
                }
            } catch (FileLocatorFileNotFoundException) {
                // keine DCA-Datei → weiter prüfen
            }

            // 2) Doctrine-Entity?
            if ($container->has('doctrine.orm.entity_manager')) {
                foreach ($container->get('doctrine.orm.entity_manager')->getMetadataFactory()->getAllMetadata() as $metadata) {
                    if (strcasecmp($metadata->getTableName(), $table) === 0) {
                        return true;
                    }
                }
            }

            // 3) Von einem anderen Katalog genutzt?
            $count = (int) Database::getInstance()
                ->prepare('SELECT COUNT(*) AS cnt FROM tl_catalog WHERE `table`=? AND id!=?')
                ->execute($table, (int) $exceptCatalogId)
                ->cnt;

            return $count > 0;
        } catch (\Throwable) {
            return true;
        }
    }
}
