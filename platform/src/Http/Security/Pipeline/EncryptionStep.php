<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;
use Cabinet\Backend\Infrastructure\Security\Encryption\SymmetricEncryption;

final class EncryptionStep
{
    public function __construct(private readonly SymmetricEncryption $encryption)
    {
    }

    public function enforce(Request $request, RouteRequirements $requirements, string $kid): void
    {
        if (!$requirements->requiresEncryption()) {
            return;
        }

        $context = $request->attribute('security_context');
        if (!$context instanceof SecurityContext) {
            throw new SecurityViolation('authentication_failed');
        }

        $encryptionHeader = $request->header(ProtocolHeaders::ENCRYPTION);
        if ($encryptionHeader === null) {
            throw new SecurityViolation('missing_header');
        }

        $secret = $context->keyForKid($kid);
        if ($secret === null) {
            throw new SecurityViolation('encryption_key_missing');
        }

        $envelope = json_decode($request->body(), true);
        if (!is_array($envelope)) {
            throw new SecurityViolation('encryption_invalid');
        }

        $plaintext = $this->encryption->decrypt($envelope, $secret);
        if ($plaintext === null) {
            throw new SecurityViolation('decryption_failed');
        }

        $request->setBody($plaintext);
    }
}
