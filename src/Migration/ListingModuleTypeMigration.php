<?php

namespace Alnv\ContaoCatalogManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\Finder;

/**
 * Migriert Frontend-Module der Listenansicht aus Catalog Manager v2.
 *
 * In v2 hieß der Modultyp "listing". Der Name war bereits vom Listing-Modul von Contao (contao/listing-bundle)
 * belegt und wurde deshalb in v3 in "listing-table" umbenannt (die Felder der Palette sind unverändert). Ohne
 * Migration werden v2-Module nicht mehr ausgegeben.
 *
 * - Umgestellt werden nur Module vom Typ "listing", die eine Katalog-Tabelle (cmTable) haben. Module des
 *   Contao-Listing-Moduls (list_table gesetzt, cmTable leer) bleiben unberührt; ist beides gesetzt, wird der
 *   Datensatz übersprungen und gemeldet.
 * - Das Hüllen-Template "mod_listing" aus v2 heißt seit v3 "mod_listing_table". Ein auf "mod_listing" gesetztes
 *   customTpl wird geleert (Standard ist dann mod_listing_table) – außer das Projekt überschreibt
 *   mod_listing.html5; dann wird customTpl auf "mod_listing" gesetzt, damit die Ausgabe gleich bleibt.
 */
class ListingModuleTypeMigration extends AbstractMigration
{
    public const OLD_TYPE = 'listing';

    public const NEW_TYPE = 'listing-table';

    public const OLD_TEMPLATE = 'mod_listing';

    private ?bool $blnHasTemplateOverride = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly string $projectDir,
    ) {
    }

    public function getName(): string
    {
        return 'Catalog Manager: Frontend-Modul "listing" (v2) in "listing-table" umbenennen';
    }

    public function shouldRun(): bool
    {
        return $this->getColumns() !== null && $this->fetchModules() !== [];
    }

    public function run(): MigrationResult
    {
        $arrColumns = $this->getColumns();

        if ($arrColumns === null) {
            return $this->createResult(true);
        }

        $intMigrated = 0;
        $arrSkipped = [];

        foreach ($this->fetchModules() as $arrModule) {
            if (\in_array('list_table', $arrColumns, true) && ($arrModule['list_table'] ?? '') !== '') {
                $arrSkipped[] = $arrModule['id'];
                continue;
            }

            $arrSet = ['type' => self::NEW_TYPE, 'tstamp' => \time()];
            $strTemplate = $this->resolveCustomTemplate((string)($arrModule['customTpl'] ?? ''));

            if ($strTemplate !== (string)($arrModule['customTpl'] ?? '')) {
                $arrSet['customTpl'] = $strTemplate;
            }

            $intMigrated += $this->connection->update('tl_module', $arrSet, ['id' => $arrModule['id'], 'type' => self::OLD_TYPE]);
        }

        $strMessage = \sprintf('%d Modul(e) auf "%s" umgestellt.', $intMigrated, self::NEW_TYPE);

        if ($arrSkipped) {
            $strMessage .= \sprintf(' Übersprungen (cmTable und list_table gesetzt, bitte manuell prüfen): ID %s.', \implode(', ', $arrSkipped));
        }

        if ($this->hasTemplateOverride()) {
            $strMessage .= ' Hinweis: Das Projekt überschreibt mod_listing.html5; bitte in mod_listing_table_*.html5 umbenennen, damit es im Backend auswählbar ist.';
        }

        return $this->createResult(true, $strMessage);
    }

    /**
     * Neues customTpl für ein migriertes Modul.
     */
    public function resolveCustomTemplate(string $strCustomTpl): string
    {
        if ($this->hasTemplateOverride()) {
            // v2 hat ohne customTpl mod_listing verwendet – das Projekt-Template weiter benutzen
            return $strCustomTpl === '' ? self::OLD_TEMPLATE : $strCustomTpl;
        }

        return $strCustomTpl === self::OLD_TEMPLATE ? '' : $strCustomTpl;
    }

    /**
     * Module vom Typ "listing" mit Katalog-Tabelle.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchModules(): array
    {
        $arrColumns = $this->getColumns() ?? [];
        $arrSelect = ['id', 'customTpl'];

        if (\in_array('list_table', $arrColumns, true)) {
            $arrSelect[] = 'list_table';
        }

        return $this->connection->fetchAllAssociative(
            'SELECT ' . \implode(', ', \array_map($this->connection->quoteIdentifier(...), $arrSelect)) . ' FROM tl_module WHERE type=? AND cmTable<>?',
            [self::OLD_TYPE, '']
        );
    }

    /**
     * Spalten von tl_module (klein geschrieben) oder null, wenn die Migration nicht anwendbar ist.
     *
     * @return list<string>|null
     */
    private function getColumns(): ?array
    {
        $objSchemaManager = $this->connection->createSchemaManager();

        if (!$objSchemaManager->tablesExist(['tl_module'])) {
            return null;
        }

        $arrColumns = \array_map('strtolower', \array_keys($objSchemaManager->listTableColumns('tl_module')));

        foreach (['type', 'cmtable', 'customtpl'] as $strRequired) {
            if (!\in_array($strRequired, $arrColumns, true)) {
                return null;
            }
        }

        return $arrColumns;
    }

    /**
     * Überschreibt das Projekt das v2-Template mod_listing.html5 (templates/ inkl. Theme-Ordner, contao/templates/)?
     */
    private function hasTemplateOverride(): bool
    {
        if ($this->blnHasTemplateOverride !== null) {
            return $this->blnHasTemplateOverride;
        }

        $arrDirs = \array_filter([$this->projectDir . '/templates', $this->projectDir . '/contao/templates'], 'is_dir');

        if (!$arrDirs) {
            return $this->blnHasTemplateOverride = false;
        }

        return $this->blnHasTemplateOverride = (new Finder())->files()->in($arrDirs)->name(self::OLD_TEMPLATE . '.html5')->hasResults();
    }
}
