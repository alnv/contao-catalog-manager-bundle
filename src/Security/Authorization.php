<?php

namespace Alnv\ContaoCatalogManagerBundle\Security;

use Contao\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class Authorization
{

    private string $strUrl = "https://shop.catalog-manager.org";

    private string $strMethod = 'key-license/getLicense';

    /** Sekunden – der Backend-Request soll nicht hängen, wenn der Lizenzserver nicht antwortet */
    private const CONNECT_TIMEOUT = 3;
    private const TIMEOUT = 5;

    public function isValid($strLicense): bool
    {

        $strLicense = \trim((string)$strLicense);

        if ($strLicense === '' || \strlen($strLicense) > 255) {
            return false;
        }

        try {
            $objClient = new Client([
                'connect_timeout' => self::CONNECT_TIMEOUT,
                'timeout' => self::TIMEOUT,
                'allow_redirects' => false,
                'http_errors' => false,
            ]);

            // Lizenzschlüssel als ein Pfadsegment kodieren (kein Pfad-/Query-Injection über "/", "?", "#")
            $objRequest = new Request('GET', $this->strUrl . '/' . $this->strMethod . '/' . \rawurlencode($strLicense), ['Accept' => 'application/json']);
            $objResponse = $objClient->send($objRequest);
        } catch (GuzzleException) {
            return false;
        }

        if ($objResponse->getStatusCode() !== 200) {
            return false;
        }

        $arrOrder = \json_decode((string)$objResponse->getBody(), true);

        if (!\is_array($arrOrder) || ($arrOrder['status'] ?? null) !== 200) {
            return false;
        }

        $strUrl = Environment::get('uri');
        $arrItems = $arrOrder['data']['order']['items'] ?? [];

        foreach ($arrItems as $arrItem) {

            if (!\in_array($arrItem['product_id'] ?? null, ['2', '3'])) {
                continue;
            }

            $arrAttributes = \array_values($arrItem['attributes'] ?? []);
            foreach ($arrAttributes as $arrData) {
                if (is_string($arrData)) {
                    $arrData = [$arrData];
                }
                if (!\is_array($arrData)) {
                    continue;
                }
                foreach ($arrData as $strDomain) {
                    if (\is_string($strDomain) && $this->parseDomain($strUrl) === $this->parseDomain($strDomain)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function parseDomain(string $strDomain): string
    {

        $strDomain = \str_replace('www.', '', $strDomain);

        $arrFragments = \parse_url($strDomain) ?: [];
        $strHost = $arrFragments['host'] ?? '';
        $strPort = $arrFragments['port'] ?? '';

        if (!$strHost) {
            return $strDomain;
        }

        return \trim($strHost) . ($strPort ? ':' . $strPort : '');
    }

    public function getUrl(): string
    {
        return $this->strUrl;
    }
}
