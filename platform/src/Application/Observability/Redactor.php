<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Observability;

final class Redactor
{
    /**
     * Sensitive keys that should be removed from audit data
     */
    private const SENSITIVE_KEYS = [
        'signature',
        'nonce',
        'ciphertext',
        'key',
        'token',
        'authorization',
        'password',
        'secret',
        'private_key',
        'api_key',
        'access_token',
        'refresh_token',
    ];

    /**
     * Redact sensitive data from an array
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function redact(array $data): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if key contains any sensitive keyword
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                // Recursively redact nested arrays
                $result[$key] = self::redact($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
}
