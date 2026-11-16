<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\Maintenance;

use Alnv\ContaoCatalogManagerBundle\Inserttags\CatalogInsertTagResolver;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\OutputType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Insert-Tags: neue Registrierung über #[AsInsertTag], Hooks nur noch für Namen mit Bindestrich.
 */
final class InsertTagRegistrationTest extends TestCase
{
    public function testResolverRegistersAllNamesAllowedByTheParser(): void
    {
        $names = [];

        foreach ((new \ReflectionClass(CatalogInsertTagResolver::class))->getMethods() as $method) {
            foreach ($method->getAttributes(AsInsertTag::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }

        sort($names);
        $this->assertSame(['active', 'active_page', 'master', 'timestamp', 'watchlist'], $names);

        foreach ($names as $name) {
            $this->assertMatchesRegularExpression('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$/', $name, 'Namensregel von Contaos InsertTagParser');
        }
    }

    public function testServiceConfiguration(): void
    {
        $services = Yaml::parseFile(\dirname(__DIR__, 2) . '/src/Resources/config/services.yml')['services'];
        $hooks = [];

        foreach ($services as $id => $definition) {
            foreach (($definition['tags'] ?? []) as $tag) {
                if (\is_array($tag) && ($tag['hook'] ?? null) === 'replaceInsertTags') {
                    $hooks[] = $id;
                }
            }
        }

        $this->assertArrayHasKey(CatalogInsertTagResolver::class, $services);
        $this->assertSame([
            'Alnv\ContaoCatalogManagerBundle\Inserttags\Inserttags',
            'Alnv\ContaoCatalogManagerBundle\Inserttags\WatchlistInsertTag',
        ], $hooks, 'nur noch die Klassen mit Bindestrich-Tags hängen am Hook; kein doppelter Dispatcher');
        $this->assertContains('contao.migration', $services['Alnv\ContaoCatalogManagerBundle\Migration\InsertTagCaseMigration']['tags']);
    }

    public function testDelegateKeepsLegacySemantics(): void
    {
        $delegate = \Closure::bind(fn (object $resolver, $tag) => $this->delegate($resolver, $tag), new CatalogInsertTagResolver(), CatalogInsertTagResolver::class);
        $tag = new \Contao\CoreBundle\InsertTag\ResolvedInsertTag('master', new \Contao\CoreBundle\InsertTag\ResolvedParameters(['headline', '-1 day']), []);

        $legacy = new class {
            public ?string $received = null;
            public mixed $return = 'A &amp; B';

            public function replace($strTag)
            {
                $this->received = $strTag;

                return $this->return;
            }
        };

        $result = $delegate($legacy, $tag);
        $this->assertSame('master::headline::-1 day', $legacy->received, 'gleicher String wie beim Hook');
        $this->assertSame('A &amp; B', $result->getValue());
        $this->assertSame(OutputType::html, $result->getOutputType(), 'Hook-Ergebnisse wurden roh eingesetzt');

        $legacy->return = false;
        $result = $delegate($legacy, $tag);
        $this->assertSame('{{master::headline::-1 day}}', $result->getValue(), 'nicht auflösbar: Tag bleibt als Text stehen');
        $this->assertSame(OutputType::text, $result->getOutputType());

        $legacy->return = 0;
        $this->assertSame('0', $delegate($legacy, $tag)->getValue());
    }
}
