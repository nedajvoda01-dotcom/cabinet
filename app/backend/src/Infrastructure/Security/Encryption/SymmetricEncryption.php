<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Encryption;

final class SymmetricEncryption
{
    public function decrypt(array $envelope, string $key): ?string
    {
        $iv = base64_decode((string) ($envelope['iv'] ?? ''), true);
        $ciphertext = base64_decode((string) ($envelope['ct'] ?? ''), true);
        $tag = base64_decode((string) ($envelope['tag'] ?? ''), true);
        $aad = $envelope['aad'] ?? '';

        if ($iv === false || $ciphertext === false || $tag === false) {
            return null;
        }

        $aadRaw = $aad !== '' ? base64_decode((string) $aad, true) : '';
        if ($aad !== '' && $aadRaw === false) {
            return null;
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aadRaw !== '' ? $aadRaw : ''
        );

        return $plaintext === false ? null : $plaintext;
    }
}
