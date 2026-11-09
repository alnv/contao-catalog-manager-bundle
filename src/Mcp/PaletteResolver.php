<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

/**
 * Ermittelt wie Contao, welche Felder für einen Datensatz gerade aktiv sind
 * (Palette nach Typ + Subpaletten der gesetzten Selektoren).
 *
 * Reine Logik ohne Contao-Abhängigkeit, damit sie testbar bleibt.
 */
final class PaletteResolver
{
    /**
     * @param array $dca    $GLOBALS['TL_DCA'][$table]
     * @param array $values aktuelle Werte des Datensatzes
     *
     * @return array{key: string, palette: string, fields: list<string>}
     */
    public static function resolve(array $dca, array $values): array
    {
        $palettes = $dca['palettes'] ?? [];
        $selectors = $palettes['__selector__'] ?? [];
        $fieldsCfg = $dca['fields'] ?? [];

        $key = 'default';

        foreach ($selectors as $selector) {
            if (self::isCheckbox($fieldsCfg[$selector] ?? [])) {
                continue;
            }

            $value = $values[$selector] ?? null;

            if (\is_scalar($value) && '' !== (string) $value && isset($palettes[(string) $value]) && \is_string($palettes[(string) $value])) {
                $key = (string) $value;
                break;
            }
        }

        $palette = \is_string($palettes[$key] ?? null) ? $palettes[$key] : '';
        $fields = self::expand(self::parse($palette), $dca, $values, 0);

        return ['key' => $key, 'palette' => $palette, 'fields' => array_values(array_unique($fields))];
    }

    /**
     * Alle Palettenschlüssel außer __selector__ (z. B. die Feldtypen).
     *
     * @return list<string>
     */
    public static function paletteKeys(array $dca): array
    {
        return array_values(array_filter(
            array_keys($dca['palettes'] ?? []),
            static fn ($k) => '__selector__' !== $k && \is_string($dca['palettes'][$k] ?? null)
        ));
    }

    /**
     * @return list<string>
     */
    public static function parse(string $palette): array
    {
        $palette = preg_replace('/\{[^}]*\}/', '', $palette) ?? '';
        $parts = preg_split('/[,;]+/', $palette) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn ($p) => '' !== $p && preg_match('/^[a-zA-Z0-9_]+$/', $p)));
    }

    private static function expand(array $fields, array $dca, array $values, int $depth): array
    {
        if ($depth > 5) {
            return $fields;
        }

        $selectors = $dca['palettes']['__selector__'] ?? [];
        $subpalettes = $dca['subpalettes'] ?? [];
        $result = [];

        foreach ($fields as $field) {
            $result[] = $field;

            if (!\in_array($field, $selectors, true)) {
                continue;
            }

            $value = $values[$field] ?? null;
            $subKey = null;

            if (self::isCheckbox($dca['fields'][$field] ?? [])) {
                if (!empty($value)) {
                    $subKey = $field;
                }
            } elseif (\is_scalar($value) && '' !== (string) $value) {
                $subKey = $field . '_' . $value;
            }

            if (null !== $subKey && \is_string($subpalettes[$subKey] ?? null)) {
                array_push($result, ...self::expand(self::parse($subpalettes[$subKey]), $dca, $values, $depth + 1));
            }
        }

        return $result;
    }

    private static function isCheckbox(array $cfg): bool
    {
        return 'checkbox' === ($cfg['inputType'] ?? null) && empty($cfg['eval']['multiple']);
    }
}
