<?php

namespace Alnv\ContaoCatalogManagerBundle\Library;

use Contao\Date;
use Contao\Environment;
use Contao\StringUtil;

class ICalendar
{

    protected array $arrEntity = [];

    public function __construct($arrEntity)
    {
        $this->arrEntity = $arrEntity;
    }

    public function getICalendarUrl(): string
    {

        global $objPage;

        return 'catalog-manager/icalendar/?' . \http_build_query([
            't' => (string)($this->arrEntity['_table'] ?? ''),
            'i' => (string)($this->arrEntity['id'] ?? ''),
            'p' => (int)($objPage->id ?? 0),
        ], '', '&');
    }


    public function getICalFile(): string
    {

        global $objPage;

        $strStartTime = $this->arrEntity['roleResolver']()->getValueByRole('startTime');
        $strStartDate = $this->arrEntity['roleResolver']()->getValueByRole('startDate');
        $strEndTime = $this->arrEntity['roleResolver']()->getValueByRole('endTime');
        $strEndDate = $this->arrEntity['roleResolver']()->getValueByRole('endDate');
        $strLocation = $this->arrEntity['roleResolver']()->getValueByRole('location');
        $strTeaser = $this->arrEntity['roleResolver']()->getValueByRole('teaser');
        $strTitle = $this->arrEntity['roleResolver']()->getValueByRole('title');
        $strCity = $this->arrEntity['roleResolver']()->getValueByRole('city');

        $strStart = $this->setICalendarDate($strStartDate . ($strStartTime ? ' ' . $strStartTime : ''), ($strStartTime ? $objPage->dateFormat . ' ' . $objPage->timeFormat : $objPage->dateFormat));
        $strEnd = $this->setICalendarDate($strEndDate . ($strEndTime ? ' ' . $strEndTime : ''), ($strEndTime ? $objPage->dateFormat . ' ' . $objPage->timeFormat : $objPage->dateFormat));

        $strHost = (string)(Environment::get('host') ?: 'catalog-manager.org');

        // Datums-/Systemwerte (bereits sicher formatiert) und Freitexte getrennt behandeln
        $arrProperties = [
            'DTSTART' => $strStart,
            'DTEND' => $strEnd,
            'DTSTAMP' => gmdate('Ymd\THis\Z'),
            'UID' => md5(($this->arrEntity['_table'] ?? '') . ':' . ($this->arrEntity['id'] ?? '')) . '@' . $this->escapeText($strHost),
            'SUMMARY' => $this->escapeText($strTitle),
            'LOCATION' => $this->escapeText(\trim($this->toText($strCity) . ($this->toText($strLocation) ? ', ' . $this->toText($strLocation) : ''), ', ')),
            'DESCRIPTION' => $this->escapeText($strTeaser),
            'URL;VALUE=URI' => $this->sanitizeUri((string)Environment::get('uri')),
        ];

        $arrLines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//catalog-manager.org//Catalog Manager//DE', 'BEGIN:VEVENT'];

        foreach ($arrProperties as $strName => $strValue) {
            if ($strValue === '' || $strValue === null) {
                continue;
            }
            $arrLines[] = $this->foldLine($strName . ':' . $strValue);
        }

        $arrLines[] = 'END:VEVENT';
        $arrLines[] = 'END:VCALENDAR';

        return \implode("\r\n", $arrLines) . "\r\n";
    }

    /**
     * Freitext nach RFC 5545 3.3.11 escapen: Backslash, Semikolon, Komma, Zeilenumbrüche.
     * Ohne Escaping lassen sich über CR/LF zusätzliche Properties (z. B. ATTACH, ORGANIZER) einschleusen.
     */
    protected function escapeText(mixed $varValue): string
    {
        $strValue = $this->toText($varValue);

        $strValue = \str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $strValue);
        $strValue = \preg_replace('/\r\n|\r|\n/', '\\n', $strValue);

        // übrige Steuerzeichen entfernen
        return (string)\preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $strValue);
    }

    protected function toText(mixed $varValue): string
    {
        if (\is_array($varValue)) {
            $varValue = \implode(', ', \array_filter(\array_map(static fn ($v) => \is_scalar($v) ? (string)$v : ($v['label'] ?? ''), $varValue)));
        }

        $strValue = StringUtil::decodeEntities(\strip_tags((string)$varValue));

        return \trim((string)\preg_replace('/[ \t]+/', ' ', $strValue));
    }

    protected function sanitizeUri(string $strUri): string
    {
        // Nur http(s) und keine Steuerzeichen/Leerzeichen
        if (!\preg_match('#^https?://#i', $strUri)) {
            return '';
        }

        return (string)\preg_replace('/[\x00-\x20\x7F]/', '', $strUri);
    }

    protected function foldLine(string $strLine): string
    {
        $arrParts = [];
        $strCurrent = '';

        foreach (\mb_str_split($strLine) as $strChar) {
            $intLimit = empty($arrParts) ? 75 : 74; // Folgezeilen beginnen mit einem Leerzeichen
            if (\strlen($strCurrent . $strChar) > $intLimit) {
                $arrParts[] = $strCurrent;
                $strCurrent = '';
            }
            $strCurrent .= $strChar;
        }

        $arrParts[] = $strCurrent;

        return \implode("\r\n ", $arrParts);
    }

    protected function setICalendarDate($strDate, $strFormat): string
    {

        if (!$strDate) {
            return '';
        }

        try {
            return date('Ymd\THis', (new Date($strDate, $strFormat))->tstamp);
        } catch (\Throwable) {
            return '';
        }
    }
}
