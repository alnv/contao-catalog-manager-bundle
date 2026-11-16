<?php

namespace Alnv\ContaoCatalogManagerBundle\Widgets;

use Contao\Date;
use Contao\FormText;

/**
 * Textfeld mit nativem Datums-/Zeit-Picker für DCA-Felder mit rgxp date, time oder datim.
 *
 * Erweitert Contaos FormText, das dasselbe bereits für rgxp=date macht (HTML5-Wert <-> Datumsformat,
 * siehe contao/core #5918), um time und datim:
 *
 *   rgxp  | <input type>   | Browser-Wert
 *   date  | date           | Y-m-d
 *   time  | time           | H:i  bzw. H:i:s
 *   datim | datetime-local | Y-m-d\TH:i  bzw. Y-m-d\TH:i:s
 *
 * Ablauf wie in Contao: Der Browser-Wert wird streng geprüft und in das konfigurierte numerische
 * Format (Date::getFormatFromRgxp – dateFormat/timeFormat/datimFormat aus Seite bzw. localconfig)
 * umgewandelt. Danach validiert Contao (Widget::validator: Validator::isDate/isTime/isDatim und
 * new Date()), die Umwandlung in den Timestamp übernimmt der Aufrufer wie DC_Table::save().
 * Angezeigt wird umgekehrt: Contao formatiert den Timestamp (Widget::getAttributesFromDca), dieses
 * Widget setzt den Wert in das HTML5-Format um.
 *
 * Werte im konfigurierten Format (z. B. "12.11.2026", Browser ohne Picker) werden weiterhin akzeptiert.
 *
 * Nicht in TL_FFL registriert (würde im Formulargenerator auftauchen); verwendet vom DcFormBuilder.
 */
class FormDateTime extends FormText
{
    public const RGXPS = ['date', 'time', 'datim'];

    private const INPUT_TYPES = ['date' => 'date', 'time' => 'time', 'datim' => 'datetime-local'];

    /** HTML5-Formate ohne bzw. mit Sekunden */
    private const HTML5_FORMATS = [
        'date' => ['Y-m-d', 'Y-m-d'],
        'time' => ['H:i', 'H:i:s'],
        'datim' => ['Y-m-d\TH:i', 'Y-m-d\TH:i:s'],
    ];

    private const HTML5_PATTERNS = [
        'date' => '/^\d{4}-\d{2}-\d{2}$/',
        'time' => '/^\d{2}:\d{2}(:\d{2})?$/',
        'datim' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/',
    ];

    public static function supports(?string $rgxp): bool
    {
        return \in_array($rgxp, self::RGXPS, true);
    }

    public function __get($strKey)
    {
        if ('type' === $strKey && isset(self::INPUT_TYPES[$this->rgxp]) && !$this->hideInput) {
            return self::INPUT_TYPES[$this->rgxp];
        }

        return parent::__get($strKey);
    }

    public function generate()
    {
        // Mit Sekunden im konfigurierten Format: Sekunden auch im Picker anbieten
        if ($this->hasSeconds()) {
            $this->arrAttributes['step'] = 1;
        }

        // placeholder ist bei date/time-Eingaben nicht zulässig
        unset($this->arrAttributes['placeholder']);

        return parent::generate();
    }

    protected function validator($varInput)
    {
        if (\is_string($varInput) && isset(self::INPUT_TYPES[$this->rgxp])) {
            $varInput = $this->fromHtml5(trim($varInput));

            // Ungültig: nicht an FormText weiterreichen – das würde "2026-02-31" zu "03.03.2026" verschieben
            if ($this->hasErrors()) {
                return $varInput;
            }
        }

        return parent::validator($varInput);
    }

    /**
     * Wert im konfigurierten Contao-Format -> HTML5-Format (für das value-Attribut).
     */
    protected function convertDate($varValue)
    {
        if (!\is_string($varValue) || '' === $varValue || !isset(self::INPUT_TYPES[$this->rgxp])) {
            return $varValue;
        }

        $source = Date::getFormatFromRgxp($this->rgxp);

        if (!preg_match('~^' . Date::getRegexp($source) . '$~i', $varValue)) {
            return $varValue;
        }

        $date = \DateTimeImmutable::createFromFormat('!' . $source, $varValue);

        return false === $date ? $varValue : $date->format($this->getHtml5Format());
    }

    /**
     * HTML5-Wert -> konfiguriertes Contao-Format. Ungültige Kalenderdaten (z. B. 2026-02-31, 25:00)
     * werden abgelehnt; PHP würde sie sonst stillschweigend auf den Folgemonat bzw. -tag verschieben.
     */
    protected function fromHtml5(string $input): string
    {
        if ('' === $input || !preg_match(self::HTML5_PATTERNS[$this->rgxp], $input)) {
            return $input;
        }

        [$short, $long] = self::HTML5_FORMATS[$this->rgxp];
        $format = \strlen($input) === \strlen(date($long, 0)) ? $long : $short;
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $input);
        $errors = \DateTimeImmutable::getLastErrors();

        if (false === $date || (\is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format($format) !== $input) {
            $this->addError('time' === $this->rgxp
                ? sprintf($GLOBALS['TL_LANG']['ERR']['time'] ?? 'Please enter the time in format "%s".', Date::getInputFormat(Date::getNumericTimeFormat()))
                : sprintf($GLOBALS['TL_LANG']['ERR']['invalidDate'] ?? 'The date "%s" does not exist.', $input));

            return $input;
        }

        return $date->format(Date::getFormatFromRgxp($this->rgxp));
    }

    private function getHtml5Format(): string
    {
        return self::HTML5_FORMATS[$this->rgxp][$this->hasSeconds() ? 1 : 0];
    }

    private function hasSeconds(): bool
    {
        return 'date' !== $this->rgxp && str_contains(Date::getFormatFromRgxp($this->rgxp), 's');
    }
}
