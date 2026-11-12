<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\DcFormBuilder;

final class DateFieldStorage
{
    private const SQL_FORMATS = [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'datetime_immutable' => 'Y-m-d H:i:s',
        'datetimetz' => 'Y-m-d H:i:s',
        'timestamp' => 'Y-m-d H:i:s',
        'time' => 'H:i:s',
        'date_immutable' => 'Y-m-d',
        'time_immutable' => 'H:i:s',
    ];

    public static function getSqlFormat(array $cfg): ?string
    {
        if (self::hasCallbacks($cfg)) {
            return null;
        }

        $sql = $cfg['sql'] ?? null;

        if (\is_array($sql)) {
            $type = strtolower((string)($sql['type'] ?? ''));
        } elseif (\is_string($sql) && preg_match('/^\s*([a-z_]+)/i', $sql, $match)) {
            $type = strtolower($match[1]);
        } else {
            return null;
        }

        return self::SQL_FORMATS[$type] ?? null;
    }

    public static function hasCallbacks(array $cfg): bool
    {
        return !empty($cfg['save_callback']) || !empty($cfg['load_callback']);
    }

    public static function fromStorage(mixed $value, array $cfg): mixed
    {
        $format = self::getSqlFormat($cfg);

        if (null === $format || !\is_string($value) || '' === $value || preg_match('/^-?\d+$/', $value)) {
            return $value;
        }

        // Leerwerte nativer Spalten (nicht bei TIME: 00:00:00 ist Mitternacht)
        if ('H:i:s' !== $format && preg_match('/^[0:\- ]+$/', $value)) {
            return '';
        }

        foreach ([$format, 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'H:i:s', 'H:i'] as $candidate) {
            $date = \DateTimeImmutable::createFromFormat('!' . $candidate, $value);

            if (false !== $date && $date->format($candidate) === $value) {
                return $date->getTimestamp();
            }
        }

        return $value;
    }

    public static function toStorage(int $timestamp, array $cfg): int|string
    {
        $format = self::getSqlFormat($cfg);

        return null === $format ? $timestamp : date($format, $timestamp);
    }
}
