<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class DoctrineSchemaListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Registry        $doctrine,
    )
    {
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        if (!Database::getInstance()->tableExists('tl_catalog')) {
            return;
        }

        $schema = $event->getSchema();
        $objCatalogs = Database::getInstance()->prepare('SELECT * FROM tl_catalog ORDER BY `table`')->execute();

        while ($objCatalogs->next()) {

            if (!$objCatalogs->table) {
                continue;
            }

            $objTable = $schema->hasTable($objCatalogs->table) ? $schema->getTable($objCatalogs->table) : $schema->createTable($objCatalogs->table);
            $arrFields = Database::getInstance()->listFields($objCatalogs->table);

            foreach ($arrFields as $strIndex => $arrField) {

                $strField = $arrField['name'];

                if (in_array($strIndex, ['PRIMARY', 'alias'])) {
                    continue;
                }

                $default = $arrField['default'];
                $unsigned = ($arrField['attributes'] ?? '') == 'unsigned';
                $notnull = ($arrField['null'] ?? '') == 'NOT NULL';
                $autoincrement = ($arrField['extra'] ?? '') == 'auto_increment';

                $origin_type = strtok(strtolower($arrField['origtype']), '(), ');
                $connection = $this->doctrine->getConnection();
                $type = $connection->getDatabasePlatform()->getDoctrineTypeMapping($origin_type);
                $length = (int)strtok('(), ');

                $arrOptions = [
                    'length' => $length,
                    'unsigned' => $unsigned,
                    'fixed' => $origin_type == 'char',
                    'default' => $default,
                    'notnull' => $notnull,
                    'scale' => null,
                    'precision' => null,
                    'autoincrement' => $autoincrement,
                    'comment' => null,
                ];

                $objTable->addColumn($strField, $type, $arrOptions);

                if ($strField == 'id') {
                    $objTable->setPrimaryKey([$strField]);
                }
            }
        }
    }
}