<?php

namespace Alnv\ContaoCatalogManagerBundle\DependencyInjection;

use Alnv\ContaoCatalogManagerBundle\Mcp\McpCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AlnvContaoCatalogManagerExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        // php-mcp/server ist optional (composer "suggest"): ohne das Paket wird der CLI-Befehl
        // catalog:mcp nicht registriert – keine unnötige Abhängigkeit/Angriffsfläche in Produktion
        if (!\class_exists(\PhpMcp\Server\Server::class)) {
            $container->removeDefinition(McpCommand::class);
        }
    }
}
