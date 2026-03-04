<?php

namespace Alnv\ContaoCatalogManagerBundle\Search\Backend\Provider;

use Alnv\ContaoCatalogManagerBundle\Library\VirtualDataContainerArray;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\Database;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class VirtualTableDataContainerProvider implements ProviderInterface
{

    public const TYPE_PREFIX = 'contao.db.';

    public function __construct(
        private readonly ContaoFramework          $contaoFramework,
        private readonly Connection               $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DcaUrlAnalyzer           $dcaUrlAnalyzer,
        private readonly TranslatorInterface      $translator
    )
    {
    }

    public function supportsType(string $type): bool
    {
        return str_starts_with($type, self::TYPE_PREFIX);
    }

    public function updateIndex(ReindexConfig $config): iterable
    {
        foreach ($this->getTables($config) as $table) {

            foreach ($this->findDocuments($table, $config) as $document) {
                yield $document;
            }
        }
    }

    public function convertDocumentToHit(Document $document): Hit|null
    {
        $document = $this->addCurrentRowToDocumentIfNotAlreadyLoaded($document);
        $row = $document->getMetadata()['row'] ?? null;

        // Entry does not exist anymore -> no hit
        if (null === $row) {
            return null;
        }

        $table = $this->getTableFromDocument($document);

        try {
            $editUrl = $this->dcaUrlAnalyzer->getEditUrl($table, (int)$document->getId());
            $viewUrl = $this->dcaUrlAnalyzer->getViewUrl($table, (int)$document->getId());
        } catch (AccessDeniedException) {
            return null;
        }

        if (null === $viewUrl) {
            return null;
        }

        $trail = $this->dcaUrlAnalyzer->getTrail($editUrl);
        $title = array_pop($trail)['label'];

        return (new Hit($document, $title, $viewUrl))
            ->withEditUrl($editUrl)
            ->withBreadcrumbs($trail)
            ->withContext($document->getSearchableContent())
            ->withMetadata(['row' => $row]);
    }

    public function isDocumentGranted(TokenInterface $token, Document $document): bool
    {
        $document = $this->addCurrentRowToDocumentIfNotAlreadyLoaded($document);
        $row = $document->getMetadata()['row'] ?? null;

        // Entry does not exist anymore -> no access
        if (null === $row) {
            return false;
        }

        return true;
    }

    public function convertTypeToVisibleType(string $type): string
    {
        $table = substr($type, \strlen(self::TYPE_PREFIX));

        return $this->translator->trans($table . '.tableLabel', [], 'contao_' . $table);
    }

    private function addCurrentRowToDocumentIfNotAlreadyLoaded(Document $document): Document
    {
        if (isset($document->getMetadata()['row'])) {
            return $document;
        }

        $row = $this->loadRow($this->getTableFromDocument($document), (int)$document->getId());

        return $document->withMetadata([...$document->getMetadata(), 'row' => false === $row ? null : $row]);
    }

    private function getTableFromDocument(Document $document): string
    {
        return $document->getMetadata()['table'] ?? '';
    }

    private function getTables(ReindexConfig $config): array
    {

        $this->contaoFramework->initialize();

        $tables = [];
        $catalogs = Database::getInstance()->prepare('SELECT * FROM tl_catalog WHERE tstamp>0')->execute();

        while ($catalogs->next()) {
            if (Database::getInstance()->tableExists($catalogs->table)) {
                $tables[] = $catalogs->table;
            }
        }

        return $tables;
    }

    private function findDocuments(string $table, ReindexConfig $reindexConfig): \Generator
    {

        $objVDataContainerArray = new VirtualDataContainerArray($table);
        $objVDataContainerArray->generate();

        if (!isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            return [];
        }

        $fieldsConfig = $GLOBALS['TL_DCA'][$table]['fields'];

        $searchableFields = array_filter(
            $fieldsConfig,
            static function (array $config): bool {
                if (\array_key_exists('backendSearch', $config)) {
                    return (bool)$config['backendSearch'];
                }

                return (bool)($config['search'] ?? false);
            },
        );

        $virtualFields = [];
        $select = array_unique(['id', ...array_map(static fn(string $field) => $virtualFields[$field] ?? $field, array_keys($searchableFields))]);
        $qb = $this->createQueryBuilderForTable($table, implode(',', $select));

        System::getContainer()
            ->get('monolog.logger.contao')
            ->log(LogLevel::ERROR, 'SELECT: ' . $table . ' -> ' . serialize($select), ['contao' => new ContaoContext(__CLASS__ . '::' . __FUNCTION__)]);

        if ($reindexConfig->getUpdateSince() && isset($GLOBALS['TL_DCA'][$table]['fields']['tstamp'])) {
            $qb->andWhere('tstamp <= ', $qb->createNamedParameter($reindexConfig->getUpdateSince()));
        }

        if ($documentIds = $reindexConfig->getLimitedDocumentIds()->getDocumentIdsForType($this->getTypeFromTable($table))) {
            $qb->expr()->in('id', $qb->createNamedParameter($documentIds, ArrayParameterType::STRING));
        }

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            $document = $this->createDocumentFromRow($table, $row, $fieldsConfig, $searchableFields);

            if ($document) {
                yield $document;
            }
        }
    }

    private function createDocumentFromRow(string $table, array $row, array $fieldsConfig, array $searchableFields): Document|null
    {
        $searchableContent = $this->extractSearchableContent($table, $row, $fieldsConfig, $searchableFields);

        if ('' === $searchableContent) {
            return null;
        }

        return (new Document((string)$row['id'], $this->getTypeFromTable($table), $searchableContent))->withMetadata(['table' => $table]);
    }

    private function getTypeFromTable(string $table): string
    {
        return self::TYPE_PREFIX . $table;
    }

    private function extractSearchableContent(string $table, array $row, array $fieldsConfig, array $searchableFields): string
    {
        $searchableContent = [];

        // Expand virtual fields
        // $row = $this->virtualFieldsHandler->expandFields($row, $table);

        foreach (array_keys($searchableFields) as $field) {
            if (isset($row[$field])) {
                $event = new FormatTableDataContainerDocumentEvent($row[$field], $fieldsConfig[$field] ?? []);
                $this->eventDispatcher->dispatch($event);
                $searchableContent[] = $event->getSearchableContent();
            }
        }

        return implode(' ', array_filter(array_unique($searchableContent)));
    }

    private function loadRow(string $table, int $id): array|false
    {

        $qb = $this->createQueryBuilderForTable($table, '*');

        return $qb
            ->andWhere('id = ' . $qb->createNamedParameter($id, ParameterType::INTEGER))
            ->fetchAssociative();
    }

    private function createQueryBuilderForTable(string $table, string $select): QueryBuilder
    {

        return $this->connection
            ->createQueryBuilder()
            ->select($select)
            ->from($table);
    }
}
