<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\FieldPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FieldPolicyTest extends TestCase
{
    public static function reasons(): iterable
    {
        yield 'Formular: Textfeld erlaubt' => ['title', FieldPolicy::CONTEXT_FORM, null];
        yield 'Formular: Systemfeld id' => ['id', FieldPolicy::CONTEXT_FORM, 'system'];
        yield 'Formular: Systemfeld pid' => ['pid', FieldPolicy::CONTEXT_FORM, 'system'];
        yield 'Formular: Passwort-Widget' => ['password', FieldPolicy::CONTEXT_FORM, 'secret'];
        yield 'Liste: Passwort nie ausgeben' => ['password', FieldPolicy::CONTEXT_LIST, 'secret'];
        yield 'Liste: geheimer Feldname' => ['secret', FieldPolicy::CONTEXT_LIST, 'secret'];
        yield 'Formular: ohne Spalte' => ['virtual', FieldPolicy::CONTEXT_FORM, 'no_column'];
        yield 'Formular: ohne Widget' => ['raw', FieldPolicy::CONTEXT_FORM, 'no_widget'];
        yield 'Formular: nicht unterstütztes Widget' => ['color', FieldPolicy::CONTEXT_FORM, 'unsupported_widget'];
        yield 'Formular: doNotShow' => ['notes', FieldPolicy::CONTEXT_FORM, 'hidden'];
        yield 'Formular: unbekanntes Feld' => ['nope', FieldPolicy::CONTEXT_FORM, 'unknown_field'];
        yield 'Formular: ungültiger Name' => ['title;DROP', FieldPolicy::CONTEXT_FORM, 'invalid_name'];
        yield 'Liste: id erlaubt' => ['id', FieldPolicy::CONTEXT_LIST, null];
        yield 'Liste: lid gesperrt' => ['lid', FieldPolicy::CONTEXT_LIST, 'system'];
        yield 'Filter: select' => ['category', FieldPolicy::CONTEXT_FILTER, null];
        yield 'Filter: Datei nicht filterbar' => ['image', FieldPolicy::CONTEXT_FILTER, 'unsupported_widget'];
        yield 'Filter: Systemfeld' => ['id', FieldPolicy::CONTEXT_FILTER, 'system'];
        yield 'Toggle: einfache Checkbox' => ['published', FieldPolicy::CONTEXT_TOGGLE, null];
        yield 'Toggle: Mehrfach-Checkbox' => ['tags', FieldPolicy::CONTEXT_TOGGLE, 'not_a_toggle'];
        yield 'Toggle: Textfeld' => ['title', FieldPolicy::CONTEXT_TOGGLE, 'not_a_toggle'];
        yield 'Intern: pid erlaubt' => ['pid', FieldPolicy::CONTEXT_INTERNAL, null];
        yield 'Intern: tstamp gesperrt' => ['tstamp', FieldPolicy::CONTEXT_INTERNAL, 'system'];
        yield 'Besitzer: id gesperrt' => ['id', FieldPolicy::CONTEXT_OWNER, 'system'];
        yield 'unbekannter Kontext' => ['title', 'foo', 'unknown_context'];
    }

    #[DataProvider('reasons')]
    public function testReason(string $field, string $context, ?string $expected): void
    {
        $policy = new FieldPolicy(Fixtures::dca());

        $this->assertSame($expected, $policy->getReason($field, $context));
        $this->assertSame(null === $expected, $policy->isEligible($field, $context));
    }

    public function testFilterKeepsOrderAndRemovesDuplicates(): void
    {
        $policy = new FieldPolicy(Fixtures::dca());

        $this->assertSame(['category', 'title'], $policy->filter(['category', 'password', 'title', 'category', 'id'], FieldPolicy::CONTEXT_FORM));
    }

    public function testWidgetMappingIsConfigurable(): void
    {
        $policy = new FieldPolicy(Fixtures::dca(), FieldPolicy::DEFAULT_WIDGETS + ['colorpicker' => 'text']);

        $this->assertTrue($policy->isEligible('color', FieldPolicy::CONTEXT_FORM));
        $this->assertSame('text', $policy->getWidgetType('color'));
        $this->assertSame('upload', $policy->getWidgetType('image'));
    }

    public function testLabelsAndOptions(): void
    {
        $policy = new FieldPolicy(Fixtures::dca());

        $this->assertSame('Titel', $policy->getLabel('title'));
        $this->assertSame('published', $policy->getLabel('published'));
        $this->assertSame(['published', 'addImage'], array_keys($policy->getOptions(FieldPolicy::CONTEXT_TOGGLE)), 'nur einfache Checkboxen');
    }

    public function testUncoveredMandatoryFields(): void
    {
        $policy = new FieldPolicy(Fixtures::dca());

        $this->assertSame(['image'], $policy->getUncoveredMandatoryFields(['title']));
        $this->assertSame([], $policy->getUncoveredMandatoryFields(['title', 'image']));
    }
}
