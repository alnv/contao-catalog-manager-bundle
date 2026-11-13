<?php

namespace Alnv\ContaoCatalogManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class InsertTagCaseMigration extends AbstractMigration
{
    /**
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'tl_content' => ['cmWizardFilterSettings', 'cmColumn', 'cmValue'],
        'tl_module' => ['cmWizardFilterSettings', 'cmColumn', 'cmValue', 'cmInfoContent'],
        'tl_catalog_field' => ['dbFilterColumn', 'dbFilterValue'],
    ];

    private const TAG_PATTERN = '/\{\{(ACTIVE_PAGE|ACTIVE|MASTER|WATCHLIST-TABLE|WATCHLIST-RESET|WATCHLIST-COUNT|WATCHLIST|TIMESTAMP|CM-USER|LAST-ADDED-MASTER-VIEW-IDS)(?=::|\||\}\})/i';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Catalog Manager: Insert-Tag-Namen kleinschreiben';
    }

    public function shouldRun(): bool
    {
        foreach ($this->getColumns() as $strTable => $arrColumns) {
            foreach ($arrColumns as $strColumn) {
                foreach ($this->fetchCandidates($strTable, $strColumn) as $arrRow) {
                    if ($this->normalize((string)$arrRow['value']) !== (string)$arrRow['value']) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $intUpdated = 0;

        foreach ($this->getColumns() as $strTable => $arrColumns) {
            foreach ($arrColumns as $strColumn) {
                foreach ($this->fetchCandidates($strTable, $strColumn) as $arrRow) {
                    $strNormalized = $this->normalize((string)$arrRow['value']);

                    if ($strNormalized !== (string)$arrRow['value']) {
                        $intUpdated += $this->connection->update($strTable, [$this->connection->quoteIdentifier($strColumn) => $strNormalized], ['id' => $arrRow['id']]);
                    }
                }
            }
        }

        return $this->createResult(true, \sprintf('%d Datensätze aktualisiert.', $intUpdated));
    }

    public function normalize(string $strValue): string
    {
        return (string)\preg_replace_callback(self::TAG_PATTERN, static fn (array $arrMatch): string => '{{' . \strtolower($arrMatch[1]), $strValue);
    }

    private function getColumns(): array
    {
        $objSchemaManager = $this->connection->createSchemaManager();
        $arrReturn = [];

        foreach (self::COLUMNS as $strTable => $arrColumns) {
            if (!$objSchemaManager->tablesExist([$strTable])) {
                continue;
            }

            $arrExisting = \array_map('strtolower', \array_keys($objSchemaManager->listTableColumns($strTable)));
            $arrReturn[$strTable] = \array_values(\array_filter($arrColumns, static fn (string $strColumn): bool => \in_array(\strtolower($strColumn), $arrExisting, true)));
        }

        return $arrReturn;
    }

    private function fetchCandidates(string $strTable, string $strColumn): array
    {
        $strColumnQuoted = $this->connection->quoteIdentifier($strColumn);

        return $this->connection->fetchAllAssociative(
            \sprintf('SELECT id, %s AS value FROM %s WHERE %s LIKE ?', $strColumnQuoted, $this->connection->quoteIdentifier($strTable), $strColumnQuoted),
            ['%{{%']
        );
    }
}
