<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Signatures;

final class SignatureVerifier
{
    public function verify(string $canonical, string $secret, string $providedSignature): bool
    {
        $expected = base64_encode(hash_hmac('sha256', $canonical, $secret, true));

        return hash_equals($expected, $providedSignature);
    }
}
