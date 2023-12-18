<?php

namespace Alnv\ContaoCatalogManagerBundle\EventListener;

use Contao\Database;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

class DoctrineSchemaListener
{

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {

        //
    }
}