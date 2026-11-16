<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\System;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Psr\Log\LogLevel;

class DoctrineSchemaListener
{
    public function __construct(private readonly ContaoFramework $framework, private readonly Registry $doctrine)
    {
        //
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        if (!Database::getInstance()->tableExists('tl_catalog')) {
            return;
        }

        $schema = $event->getSchema();
        $catalogs = Database::getInstance()
            ->prepare('SELECT * FROM tl_catalog ORDER BY `table`')
            ->execute();

        while ($catalogs->next()) {
            if (!$catalogs->table) {
                continue;
            }

            $table = $schema->hasTable($catalogs->table) ? $schema->getTable($catalogs->table) : $schema->createTable($catalogs->table);
            $fields = Database::getInstance()->listFields($catalogs->table);

            foreach ($fields as $strIndex => $fieldData) {
                $field = $fieldData['name'] ?? '';
                if (\in_array($strIndex, ['PRIMARY', 'alias'])) {
                    continue;
                }

                $unsigned = ($fieldData['attributes'] ?? '') === 'unsigned';
                $notnull = ($fieldData['null'] ?? '') === 'NOT NULL';
                $autoincrement = ($fieldData['extra'] ?? '') === 'auto_increment';

                $origType = \strtolower($fieldData['origtype'] ?? '');

                $origin_type = $origType;
                $precision = null;
                $scale = null;

                if (\preg_match('/^([a-z]+)(?:\((\d+)(?:,\s*(\d+))?\))?/i', $origType, $matches)) {
                    $origin_type = $matches[1];
                    $precision = isset($matches[2]) ? (int)$matches[2] : null;
                    $scale = isset($matches[3]) ? (int)$matches[3] : null;
                }

                $connection = $this->doctrine->getConnection();

                try {
                    $type = $connection->getDatabasePlatform()->getDoctrineTypeMapping($origin_type);

                    $options = [
                        'unsigned' => $unsigned,
                        'fixed' => $origin_type === 'char',
                        'notnull' => $notnull,
                        'autoincrement' => $autoincrement,
                    ];

                    if ($type === 'decimal') {
                        $options['precision'] = $precision ?? 10;
                        $options['scale'] = $scale ?? 0;
                    } elseif ($precision !== null && $precision > 0) {
                        $options['length'] = $precision;
                    }

                    if (isset($fieldData['default'])) {
                        if ($origin_type === 'char' && $precision === 1) {
                            $options['default'] = \substr((string)$fieldData['default'], 0, 1);
                        } else {
                            $options['default'] = $fieldData['default'];
                        }
                    }

                    $table->addColumn($field, $type, $options);
                    if ($field == 'id') {
                        $table->setPrimaryKey([$field]);
                    }
                } catch (\Exception $error) {
                    System::getContainer()
                        ->get('monolog.logger.contao')
                        ->log(LogLevel::ERROR, $error->getMessage(), ['contao' => new ContaoContext(__CLASS__ . '::' . __FUNCTION__)]);
                }
            }
        }
    }
}