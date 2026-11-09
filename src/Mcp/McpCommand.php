<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Contao\CoreBundle\Framework\ContaoFramework;
use PhpMcp\Schema\ToolAnnotations;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Startet den MCP-Server über stdio:
 *
 *   php vendor/bin/contao-console catalog:mcp --env=prod
 */
#[AsCommand(name: 'catalog:mcp', description: 'Startet den MCP-Server für den Catalog-Manager (stdio)')]
final class McpCommand extends Command
{
    private const VERSION = '1.0.0';

    public function __construct(
        private readonly ContaoFramework $framework,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('read-only', null, InputOption::VALUE_NONE, 'Nur lesende Werkzeuge erlauben')
            ->addOption('language', null, InputOption::VALUE_REQUIRED, 'Sprache für Feldbezeichnungen', 'de');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // stdout gehört allein dem MCP-Protokoll: PHP-Meldungen nach stderr umleiten.
        ini_set('display_errors', 'stderr');

        $language = (string) $input->getOption('language');
        $readOnly = (bool) $input->getOption('read-only');

        $this->framework->initialize();
        $GLOBALS['TL_LANGUAGE'] = $language;

        $tools = new Tools(
            new DcaRecordManager($this->projectDir, $language),
            new TemplateManager($this->projectDir),
            $readOnly,
        );

        $builder = Server::make()
            ->withServerInfo('Contao Catalog-Manager', self::VERSION)
            ->withContainer(new InstanceContainer([Tools::class => $tools]))
            ->withInstructions(<<<'TXT'
                Werkzeuge für den Catalog-Manager (v4) einer Contao-5-Website.
                Vorgehen: zuerst catalog_overview, dann config_describe für die Tabelle und den gewünschten Typ,
                erst danach record_create / record_update. Alle Änderungen laufen durch dieselbe Logik wie im
                Contao-Backend (Callbacks legen z. B. Datentabellen und Spalten an).
                Tabellen: tl_catalog (Kataloge), tl_catalog_field (Felder, pid = Katalog-ID),
                tl_catalog_option (Optionen, pid = Feld-ID), tl_catalog_palette (Paletten, pid = Katalog-ID),
                tl_module (Typen listing-table, master, listing-map), tl_content (Typ listview, pid + ptable)
                und die Datentabellen der Kataloge (Einträge).
                Löschen immer erst ohne confirm aufrufen, die Vorschau zeigen und nachfragen.
                TXT);

        foreach ($this->toolDefinitions($readOnly) as $name => $definition) {
            $builder->withTool(
                [Tools::class, $definition['method']],
                $name,
                $definition['description'],
                $definition['annotations'],
                $definition['schema'],
            );
        }

        $builder->build()->listen(new StdioServerTransport());

        return Command::SUCCESS;
    }

    private function toolDefinitions(bool $readOnly): array
    {
        $table = ['type' => 'string', 'description' => 'Tabellenname, z. B. tl_catalog, tl_catalog_field, tl_module oder die Datentabelle eines Katalogs'];
        $id = ['type' => 'integer', 'minimum' => 1, 'description' => 'ID des Datensatzes'];
        $read = ToolAnnotations::make(readOnlyHint: true, destructiveHint: false, openWorldHint: false);
        $write = ToolAnnotations::make(readOnlyHint: false, destructiveHint: false, openWorldHint: false);
        $destructive = ToolAnnotations::make(readOnlyHint: false, destructiveHint: true, openWorldHint: false);

        $definitions = [
            'catalog_overview' => [
                'method' => 'catalogOverview',
                'description' => 'Übersicht aller Kataloge mit Tabellen, Feldern, Anzahl Einträge sowie der Listen-/Detail-Module und Inhaltselemente.',
                'annotations' => $read,
                'schema' => self::schema(),
            ],
            'config_describe' => [
                'method' => 'configDescribe',
                'description' => 'Beschreibt die Konfigurationsmöglichkeiten einer Tabelle: aktive Felder mit Bezeichnung, Eingabetyp, Pflichtangabe und erlaubten Optionen. Die Felder hängen vom Typ ab, daher context angeben (z. B. {"type":"select"} für tl_catalog_field oder {"type":"master"} für tl_module).',
                'annotations' => $read,
                'schema' => self::schema([
                    'table' => $table,
                    'context' => ['type' => ['object', 'array'], 'description' => 'Werte für die Palettenwahl, z. B. {"type":"select"}'],
                    'id' => array_merge($id, ['description' => 'Optional: bestehenden Datensatz als Grundlage verwenden']),
                ], ['table']),
            ],
            'record_list' => [
                'method' => 'recordList',
                'description' => 'Listet Datensätze einer freigegebenen Tabelle (Konfiguration, Module oder Katalogeinträge).',
                'annotations' => $read,
                'schema' => self::schema([
                    'table' => $table,
                    'where' => ['type' => ['object', 'array'], 'description' => 'Filter feld => Wert (Gleichheit), z. B. {"pid": 3}'],
                    'orderBy' => ['type' => 'string', 'description' => 'z. B. "sorting" oder "title DESC"'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 500, 'default' => 50],
                    'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
                    'columns' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Nur diese Spalten zurückgeben'],
                ], ['table']),
            ],
            'record_get' => [
                'method' => 'recordGet',
                'description' => 'Liest einen einzelnen Datensatz.',
                'annotations' => $read,
                'schema' => self::schema(['table' => $table, 'id' => $id], ['table', 'id']),
            ],
            'record_create' => [
                'method' => 'recordCreate',
                'description' => 'Legt einen Datensatz an, mit derselben Logik wie im Backend (legt z. B. für einen neuen Katalog die Datentabelle an, für ein neues Feld die Spalte). Vorher config_describe aufrufen.',
                'annotations' => $write,
                'schema' => self::schema([
                    'table' => $table,
                    'values' => ['type' => 'object', 'description' => 'Feldwerte; Mehrfachwerte als Array, Checkboxen als true/false, Dateien als UUID oder Pfad'],
                    'pid' => ['type' => 'integer', 'minimum' => 0, 'description' => 'Elterndatensatz (Feld: Katalog-ID, Option: Feld-ID, Inhaltselement: Artikel-ID)'],
                    'ptable' => ['type' => 'string', 'description' => 'Nur bei dynamischer Elterntabelle, z. B. tl_article für tl_content'],
                ], ['table', 'values']),
            ],
            'record_update' => [
                'method' => 'recordUpdate',
                'description' => 'Ändert Felder eines Datensatzes; nicht angegebene Felder bleiben unverändert.',
                'annotations' => $write,
                'schema' => self::schema([
                    'table' => $table,
                    'id' => $id,
                    'values' => ['type' => 'object', 'description' => 'Nur die zu ändernden Felder'],
                ], ['table', 'id', 'values']),
            ],
            'record_delete' => [
                'method' => 'recordDelete',
                'description' => 'Löscht einen Datensatz samt Unterdatensätzen (bei Katalogen auch die Datentabelle). Ohne confirm=true nur Vorschau. Vor dem Löschen wird eine JSON-Sicherung in var/catalog-mcp/backups angelegt.',
                'annotations' => $destructive,
                'schema' => self::schema([
                    'table' => $table,
                    'id' => $id,
                    'confirm' => ['type' => 'boolean', 'default' => false],
                ], ['table', 'id']),
            ],
            'template_list' => [
                'method' => 'templateList',
                'description' => 'Listet eigene Catalog-Manager-Templates im Ordner /templates und die Standard-Templates des Bundles.',
                'annotations' => $read,
                'schema' => self::schema(),
            ],
            'template_read' => [
                'method' => 'templateRead',
                'description' => 'Liest ein Template (eigenes oder Standard des Bundles, z. B. cm_listing_default).',
                'annotations' => $read,
                'schema' => self::schema([
                    'name' => ['type' => 'string', 'description' => 'Name ohne Endung'],
                    'folder' => ['type' => 'string', 'description' => 'Optionaler Unterordner in /templates'],
                ], ['name']),
            ],
            'template_context' => [
                'method' => 'templateContext',
                'description' => 'Liefert für einen Katalog die Felder, Rollen und verfügbaren Template-Variablen, damit ein passendes Template geschrieben werden kann.',
                'annotations' => $read,
                'schema' => self::schema(['table' => array_merge($table, ['description' => 'Datentabelle des Katalogs'])], ['table']),
            ],
            'template_write' => [
                'method' => 'templateWrite',
                'description' => 'Schreibt ein .html5-Template nach /templates (nach PHP-Syntaxprüfung). Erlaubte Präfixe: cm_listing_, cm_master_, cm_map_view_, mod_listing, mod_master, ce_listview, ce_watchlist.',
                'annotations' => $write,
                'schema' => self::schema([
                    'name' => ['type' => 'string', 'description' => 'z. B. cm_listing_immobilien'],
                    'content' => ['type' => 'string', 'description' => 'Vollständiger Template-Inhalt (HTML mit PHP)'],
                    'folder' => ['type' => 'string', 'description' => 'Optionaler Unterordner in /templates'],
                    'overwrite' => ['type' => 'boolean', 'default' => false],
                ], ['name', 'content']),
            ],
            'template_delete' => [
                'method' => 'templateDelete',
                'description' => 'Entfernt ein eigenes Template (mit Sicherung). Ohne confirm=true passiert nichts.',
                'annotations' => $destructive,
                'schema' => self::schema([
                    'name' => ['type' => 'string'],
                    'folder' => ['type' => 'string'],
                    'confirm' => ['type' => 'boolean', 'default' => false],
                ], ['name']),
            ],
        ];

        if ($readOnly) {
            $definitions = array_filter($definitions, static fn ($d) => true === $d['annotations']->readOnlyHint);
        }

        return $definitions;
    }

    private static function schema(array $properties = [], array $required = []): array
    {
        $schema = ['type' => 'object', 'properties' => $properties ?: new \stdClass()];

        if ($required) {
            $schema['required'] = $required;
        }

        return $schema;
    }
}
