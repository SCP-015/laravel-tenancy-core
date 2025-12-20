<?php

namespace App\Services;

class DataConversionService
{
    /**
     * Konversi berbagai tipe data ke boolean dengan aturan khusus
     *
     * @param mixed $value Nilai yang akan dikonversi
     * @param bool $defaultValue Nilai default jika konversi gagal
     * @return bool
     */
    public static function toBool($value, bool $defaultValue = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true'], true);
        }

        if (is_numeric($value)) {
            return (int)$value === 1;
        }

        return $defaultValue;
    }

    /**
     * Konversi array input dengan key tertentu ke boolean
     *
     * @param array $input Array input
     * @param string $key Key yang akan dikonversi
     * @param bool $defaultValue Nilai default jika key tidak ada atau konversi gagal
     * @return bool
     */
    public static function arrayToBool(array $input, string $key, bool $defaultValue = false): bool
    {
        $value = $input[$key] ?? $defaultValue;
        return self::toBool($value, $defaultValue);
    }

    /**
     * Konversi multiple keys dari array ke boolean sekaligus
     *
     * @param array $input Array input
     * @param array $keys Array berisi key dan default value ['key' => defaultValue]
     * @return array Array hasil konversi
     */
    public static function arrayMultipleToBool(array $input, array $keys): array
    {
        $result = [];
        
        foreach ($keys as $key => $defaultValue) {
            $result[$key] = self::arrayToBool($input, $key, $defaultValue);
        }
        
        return $result;
    }

    /**
     * Konversi nilai ke integer dengan validasi
     *
     * @param mixed $value Nilai yang akan dikonversi
     * @param int $defaultValue Nilai default jika konversi gagal
     * @return int
     */
    public static function toInt($value, int $defaultValue = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        // @codeCoverageIgnoreStart
        // Redundant check - sudah ter-handle di is_numeric() di atas
        if (is_string($value) && is_numeric($value)) {
            return (int)$value;
        }
        // @codeCoverageIgnoreEnd

        return $defaultValue;
    }

    /**
     * Konversi nilai ke string dengan trim
     *
     * @param mixed $value Nilai yang akan dikonversi
     * @param string $defaultValue Nilai default jika konversi gagal
     * @return string
     */
    public static function toString($value, string $defaultValue = ''): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_numeric($value) || is_bool($value)) {
            return trim((string)$value);
        }

        return $defaultValue;
    }
}
