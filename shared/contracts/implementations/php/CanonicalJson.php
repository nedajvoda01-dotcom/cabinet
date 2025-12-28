<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

final class CanonicalJson
{
    public static function encode(mixed $value): string
    {
        return self::encodeValue($value);
    }

    private static function encodeValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                $normalized = array_map(fn ($item) => json_decode(self::encodeValue($item), true, flags: JSON_THROW_ON_ERROR), $value);
                return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            ksort($value);
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = json_decode(self::encodeValue($item), true, flags: JSON_THROW_ON_ERROR);
            }

            return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
