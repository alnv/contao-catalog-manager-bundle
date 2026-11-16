<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Einheitliches Antwortformat der AJAX-Anfragen der Frontend-Verwaltung.
 *
 *   {
 *     "status":   "success" | "validation_error" | "error",
 *     "code":     "ok" | "validation_failed" | "not_authenticated" | "forbidden" | "not_found"
 *                 | "csrf_invalid" | "bad_request" | "database_error" | "server_error",
 *     "message":  string|null   – für den Benutzer bestimmt, nie technische Details
 *     "messages": [{type, text}] – Statusmeldungen (z. B. "gespeichert")
 *     "view":     "list" | "form" | "delete" | "denied" | null
 *     "html":     string|null   – gerenderte Ansicht (nur bei success/validation_error)
 *     "url":      string|null   – kanonische URL der Ansicht (für die Browser-History)
 *     "errors":   {feld: [meldung, …]}
 *     "record":   {id: int}|null
 *     "assets":   {css: [], js: [], html: []} – von Widgets angeforderte Ressourcen
 *     "token":    string        – frisches Request-Token, in jeder Antwort
 *   }
 *
 * Statuscodes: 200 Erfolg, 400 ungültige Anfrage, 401 nicht (mehr) angemeldet, 403 keine Berechtigung
 * oder ungültiges Token, 404 Datensatz nicht vorhanden/nicht erlaubt (bewusst nicht unterscheidbar),
 * 422 Validierungsfehler, 500 Datenbank- oder sonstiger Serverfehler.
 */
final class AjaxResponse
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_VALIDATION = 'validation_error';
    public const STATUS_ERROR = 'error';

    public const CODE_OK = 'ok';
    public const CODE_VALIDATION = 'validation_failed';
    public const CODE_NOT_AUTHENTICATED = 'not_authenticated';
    public const CODE_FORBIDDEN = 'forbidden';
    public const CODE_NOT_FOUND = 'not_found';
    public const CODE_CSRF = 'csrf_invalid';
    public const CODE_BAD_REQUEST = 'bad_request';
    public const CODE_DATABASE = 'database_error';
    public const CODE_SERVER = 'server_error';

    public const HTTP_STATUS = [
        self::CODE_OK => 200,
        self::CODE_VALIDATION => 422,
        self::CODE_NOT_AUTHENTICATED => 401,
        self::CODE_FORBIDDEN => 403,
        self::CODE_NOT_FOUND => 404,
        self::CODE_CSRF => 403,
        self::CODE_BAD_REQUEST => 400,
        self::CODE_DATABASE => 500,
        self::CODE_SERVER => 500,
    ];

    private const DEFAULTS = [
        'message' => null,
        'messages' => [],
        'view' => null,
        'html' => null,
        'url' => null,
        'errors' => [],
        'record' => null,
        'assets' => ['css' => [], 'js' => [], 'html' => []],
    ];

    public static function classify(\Throwable $e): string
    {
        if ($e instanceof AccessDeniedException) {
            return match ($e->getLevel()) {
                AccessDeniedException::NOT_AUTHENTICATED => self::CODE_NOT_AUTHENTICATED,
                AccessDeniedException::RECORD => self::CODE_NOT_FOUND,
                default => self::CODE_FORBIDDEN,
            };
        }

        if ($e instanceof \InvalidArgumentException) {
            return self::CODE_VALIDATION;
        }

        for ($current = $e; null !== $current; $current = $current->getPrevious()) {
            if (is_a($current, 'Doctrine\DBAL\Exception') || $current instanceof \PDOException) {
                return self::CODE_DATABASE;
            }
        }

        return self::CODE_SERVER;
    }

    public static function statusFor(string $code): int
    {
        return self::HTTP_STATUS[$code] ?? 500;
    }

    /**
     * @param array<string, mixed> $payload siehe Klassenbeschreibung
     */
    public static function create(string $code, string $token, array $payload = []): JsonResponse
    {
        $status = self::statusFor($code);

        $data = array_merge(
            ['status' => match (true) {
                self::CODE_OK === $code => self::STATUS_SUCCESS,
                self::CODE_VALIDATION === $code => self::STATUS_VALIDATION,
                default => self::STATUS_ERROR,
            }, 'code' => isset(self::HTTP_STATUS[$code]) ? $code : self::CODE_SERVER],
            self::DEFAULTS,
            array_intersect_key($payload, self::DEFAULTS),
            ['token' => $token],
        );

        $data['errors'] = (object)$data['errors'];

        $response = new JsonResponse(null, $status);
        $response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_INVALID_UTF8_SUBSTITUTE);
        $response->setData($data);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
