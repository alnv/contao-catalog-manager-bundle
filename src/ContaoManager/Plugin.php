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
use Terminal42\DcawizardBundle\Terminal42DcawizardBundle;
use Terminal42\DcMultilingualBundle\Terminal42DcMultilingualBundle;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{

    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(AlnvContaoCatalogManagerBundle::class)
                ->setReplace(['contao-catalog-manager-bundle', 'contao-catalog-manager-multilingual-adapter-bundle'])
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    Terminal42DcawizardBundle::class,
                    Terminal42DcMultilingualBundle::class,
                    AlnvContaoWidgetCollectionBundle::class,
                    AlnvContaoTranslationManagerBundle::class
                ])
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__ . '/../Resources/config/routing.yml')
            ->load(__DIR__ . '/../Resources/config/routing.yml');
    }
}