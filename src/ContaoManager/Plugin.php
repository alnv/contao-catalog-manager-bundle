<?php

namespace Alnv\ContaoCatalogManagerBundle\ContaoManager;

use Alnv\ContaoCatalogManagerBundle\AlnvContaoCatalogManagerBundle;
use Alnv\ContaoTranslationManagerBundle\AlnvContaoTranslationManagerBundle;
use Alnv\ContaoWidgetCollectionBundle\AlnvContaoWidgetCollectionBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{

    public function getBundles(ParserInterface $parser): array
    {

        return [
            BundleConfig::create(AlnvContaoCatalogManagerBundle::class)
                ->setReplace(['contao-catalog-manager-bundle'])
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    AlnvContaoWidgetCollectionBundle::class,
                    AlnvContaoTranslationManagerBundle::class
                ])
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {

        $strRoutingYmlFile = 'routing.yml';
        if (version_compare(ContaoCoreBundle::getVersion(), '5.4.0', '>=')) {
            $strRoutingYmlFile = 'routing_c5.yml';
        }

        return $resolver
            ->resolve(__DIR__ . '/../Resources/config/' . $strRoutingYmlFile)
            ->load(__DIR__ . '/../Resources/config/' . $strRoutingYmlFile);
    }
}