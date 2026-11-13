<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Maintenance;

use Alnv\ContaoCatalogManagerBundle\Helper\Toolkit;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Toolkit::generatePageUrl() ersetzt PageModel::getFrontendUrl()/getAbsoluteUrl() mit gleichem Verhalten.
 */
final class PageUrlTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        System::setContainer($this->container);
    }

    public function testRelativeAndAbsoluteUrls(): void
    {
        $page = $this->createMock(PageModel::class);
        $page->expects($this->exactly(2))->method('loadDetails')->willReturnSelf();

        $generator = $this->createMock(ContentUrlGenerator::class);
        $generator->expects($this->exactly(2))->method('generate')->willReturnCallback(
            fn (object $content, array $parameters, int $type) => ($type === UrlGeneratorInterface::ABSOLUTE_URL ? 'https://example.org' : '') . '/seite' . $parameters['parameters']
        );
        $this->container->set('contao.routing.content_url_generator', $generator);

        $this->assertSame('/seite/mein-eintrag', Toolkit::generatePageUrl($page, '/mein-eintrag'));
        $this->assertSame('https://example.org/seite/a/b', Toolkit::generatePageUrl($page, '/a/b', true));
    }

    public function testNotRoutablePageThrowsResourceNotFound(): void
    {
        $page = $this->createMock(PageModel::class);
        $page->method('__get')->willReturn(7);

        $generator = $this->createMock(ContentUrlGenerator::class);
        $generator->method('generate')->willThrowException(new RouteNotFoundException());
        $registry = $this->createMock(PageRegistry::class);
        $registry->method('isRoutable')->willReturn(false);
        $this->container->set('contao.routing.content_url_generator', $generator);
        $this->container->set('contao.routing.page_registry', $registry);

        $this->expectException(ResourceNotFoundException::class);
        Toolkit::generatePageUrl($page);
    }

    public function testRoutablePageRethrowsRouteNotFound(): void
    {
        $page = $this->createMock(PageModel::class);

        $generator = $this->createMock(ContentUrlGenerator::class);
        $generator->method('generate')->willThrowException(new RouteNotFoundException('x'));
        $registry = $this->createMock(PageRegistry::class);
        $registry->method('isRoutable')->willReturn(true);
        $this->container->set('contao.routing.content_url_generator', $generator);
        $this->container->set('contao.routing.page_registry', $registry);

        $this->expectException(RouteNotFoundException::class);
        Toolkit::generatePageUrl($page);
    }
}
