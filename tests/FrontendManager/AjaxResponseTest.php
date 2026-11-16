<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessDeniedException;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AjaxResponse;
use Alnv\ContaoCatalogManagerBundle\Modules\FrontendManagerModule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AjaxResponseTest extends TestCase
{
    public static function exceptions(): iterable
    {
        yield 'nicht angemeldet' => [new AccessDeniedException(AccessDeniedException::NOT_AUTHENTICATED), 'not_authenticated', 401];
        yield 'Modul' => [new AccessDeniedException(AccessDeniedException::MODULE), 'forbidden', 403];
        yield 'Tabelle' => [new AccessDeniedException(AccessDeniedException::TABLE), 'forbidden', 403];
        yield 'Feld' => [new AccessDeniedException(AccessDeniedException::FIELD), 'forbidden', 403];
        yield 'Operator' => [new AccessDeniedException(AccessDeniedException::OPERATION), 'forbidden', 403];
        yield 'Datensatz (nicht unterscheidbar von nicht vorhanden)' => [new AccessDeniedException(AccessDeniedException::RECORD), 'not_found', 404];
        yield 'fachlicher Fehler' => [new \InvalidArgumentException('Pflichtfeld fehlt'), 'validation_failed', 422];
        yield 'Datenbank (verschachtelt)' => [new \RuntimeException('x', 0, new \PDOException('SQLSTATE[42S22]')), 'database_error', 500];
        yield 'unerwartet' => [new \LogicException('boom'), 'server_error', 500];
    }

    #[DataProvider('exceptions')]
    public function testClassify(\Throwable $e, string $code, int $status): void
    {
        $this->assertSame($code, AjaxResponse::classify($e));
        $this->assertSame($status, AjaxResponse::statusFor($code));
    }

    public function testSuccessFormat(): void
    {
        $response = AjaxResponse::create(AjaxResponse::CODE_OK, 'tok', ['view' => 'list', 'html' => '<p>x</p>', 'secret' => 'dropped']);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status', 'code', 'message', 'messages', 'view', 'html', 'url', 'errors', 'record', 'assets', 'token'], array_keys($data));
        $this->assertSame('success', $data['status']);
        $this->assertSame('tok', $data['token']);
        $this->assertArrayNotHasKey('secret', $data);
        $this->assertStringContainsString('"errors":{}', $response->getContent(), 'leere Fehlerliste als Objekt');
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testValidationFormat(): void
    {
        $response = AjaxResponse::create(AjaxResponse::CODE_VALIDATION, 't', ['errors' => ['title' => ['Pflichtfeld']]]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('validation_error', $data['status']);
        $this->assertSame(['title' => ['Pflichtfeld']], $data['errors']);
    }

    public function testUnknownCodeBecomesServerError(): void
    {
        $response = AjaxResponse::create('whatever', 't');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('server_error', json_decode($response->getContent(), true)['code']);
    }

    public function testHtmlIsSafelyEncoded(): void
    {
        $response = AjaxResponse::create(AjaxResponse::CODE_OK, 't', ['html' => "</script><b>\xB1</b>"]);

        $this->assertIsArray(json_decode($response->getContent(), true), 'ungültiges UTF-8 bricht die Antwort nicht');
        $this->assertStringNotContainsString('</script>', $response->getContent());
    }

    public static function urls(): iterable
    {
        yield ['/liste?_cmfm_ajax=5', '/liste'];
        yield ['/liste?_cmfm_ajax=5&amp;page_e5=2', '/liste?page_e5=2'];
        yield ['/liste?page_e5=2&amp;_cmfm_ajax=5', '/liste?page_e5=2'];
        yield ['/liste?a=1&amp;_cmfm_ajax=5&amp;b=2', '/liste?a=1&amp;b=2'];
        yield ['<a href="/l?_cmfm_ajax=5&page_e5=3">', '<a href="/l?page_e5=3">'];
        yield ['/liste?x_cmfm_ajax=5', '/liste?x_cmfm_ajax=5'];
    }

    #[DataProvider('urls')]
    public function testStripAjaxParameter(string $input, string $expected): void
    {
        $this->assertSame($expected, FrontendManagerModule::stripAjaxParameter($input));
    }
}
