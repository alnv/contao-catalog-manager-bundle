<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Contao\Controller;
use Contao\Database;
use Contao\DC_Table;
use Contao\System;

final class TableProvider
{
    /** Tabellen, die nie freigegeben werden – unabhängig von der Konfiguration. */
    public const DENIED_TABLES = [
        'tl_article', 'tl_content', 'tl_cron_job', 'tl_crawl_queue', 'tl_files', 'tl_form', 'tl_form_field',
        'tl_image_size', 'tl_image_size_item', 'tl_layout', 'tl_log', 'tl_member', 'tl_member_group',
        'tl_message_queue', 'tl_module', 'tl_opt_in', 'tl_opt_in_related', 'tl_page', 'tl_preview_link',
        'tl_remember_me', 'tl_search', 'tl_search_index', 'tl_search_term', 'tl_settings', 'tl_theme',
        'tl_trusted_device', 'tl_undo', 'tl_user', 'tl_user_group', 'tl_version', 'tl_watchlist',
    ];

    /** Präfixe, deren Tabellen nie freigegeben werden (Konfiguration des Catalog Managers selbst). */
    public const DENIED_PREFIXES = ['tl_catalog', 'tl_user_', 'tl_member_', 'tl_search_', 'tl_opt_in'];

    /** @var array<string, string>|null */
    private ?array $catalogTables = null;

    /**
     * @param list<string>|null $contaoAllowlist freigegebene tl_*-Tabellen; null = aus $GLOBALS['CM_FRONTEND_MANAGER']
     */
    public function __construct(private readonly ?array $contaoAllowlist = null)
    {
    }

    public static function isDeniedTable(string $table): bool
    {
        if (!ManagerConfig::isIdentifier($table)) {
            return true;
        }

        $table = strtolower($table);

        if (\in_array($table, self::DENIED_TABLES, true)) {
            return true;
        }

        foreach (self::DENIED_PREFIXES as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function getAvailableTables(): array
    {
        $contao = [];

        foreach ($this->getContaoAllowlist() as $table) {
            if ($this->isAllowed($table)) {
                $contao[$table] = $table;
            }
        }

        $catalog = [];

        foreach ($this->getCatalogTables() as $table => $label) {
            if ($this->isAllowed($table)) {
                $catalog[$table] = $label . ' (' . $table . ')';
            }
        }

        return array_filter(['catalog' => $catalog, 'contao' => $contao]);
    }

    public function isCatalogTable(string $table): bool
    {
        return isset($this->getCatalogTables()[$table]);
    }

    public function isAllowed(string $table): bool
    {
        if (self::isDeniedTable($table)) {
            return false;
        }

        $isCatalog = $this->isCatalogTable($table);

        if (!$isCatalog && !\in_array($table, $this->getContaoAllowlist(), true)) {
            return false;
        }

        if (!Database::getInstance()->tableExists($table)) {
            return false;
        }

        $dca = $this->loadDca($table);

        if (!isset($dca['fields']) || !\is_array($dca['fields'])) {
            return false;
        }

        // Nur tabellenbasierte DataContainer (DC_Table und Ableitungen wie DC_Multilingual)
        $driver = $dca['config']['dataContainer'] ?? null;

        if (\is_string($driver) && class_exists($driver) && !is_a($driver, DC_Table::class, true)) {
            return false;
        }

        // Tabellen im Nur-Lese-Modus nicht beschreiben
        return empty($dca['config']['notEditable']) || $isCatalog;
    }

    public function loadDca(string $table): array
    {
        if (!ManagerConfig::isIdentifier($table)) {
            return [];
        }

        System::loadLanguageFile($table);
        Controller::loadDataContainer($table);

        return $GLOBALS['TL_DCA'][$table] ?? [];
    }

    /**
     * @return array<string, string> table => Katalogname
     */
    public function getCatalogTables(): array
    {
        if (null !== $this->catalogTables) {
            return $this->catalogTables;
        }

        $this->catalogTables = [];
        $result = Database::getInstance()->execute("SELECT `table`, name FROM tl_catalog WHERE `table`!='' ORDER BY name");

        while ($result->next()) {
            if (ManagerConfig::isIdentifier((string)$result->table)) {
                $this->catalogTables[(string)$result->table] = (string)$result->name;
            }
        }

        return $this->catalogTables;
    }

    /**
     * @return list<string>
     */
    private function getContaoAllowlist(): array
    {
        $list = $this->contaoAllowlist ?? ($GLOBALS['CM_FRONTEND_MANAGER']['tables'] ?? []);

        return array_values(array_filter(array_map('strval', (array)$list), static fn($t) => str_starts_with($t, 'tl_') && !self::isDeniedTable($t)));
    }
}
