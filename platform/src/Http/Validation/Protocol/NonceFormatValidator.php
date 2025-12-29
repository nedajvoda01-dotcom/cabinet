<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Validation\Protocol;

final class NonceFormatValidator
{
    public function isValid(string $nonce): bool
    {
        $length = strlen($nonce);
        if ($length < 16 || $length > 128) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9_-]+$/', $nonce);
    }
}
