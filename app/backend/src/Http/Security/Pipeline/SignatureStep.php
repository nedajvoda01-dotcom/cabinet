<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;
use Cabinet\Backend\Infrastructure\Security\Signatures\SignatureCanonicalizer;
use Cabinet\Backend\Infrastructure\Security\Signatures\SignatureVerifier;
use Cabinet\Backend\Infrastructure\Security\Signatures\StringToSignBuilder;

final class SignatureStep
{
    public function __construct(
        private readonly StringToSignBuilder $builder,
        private readonly SignatureCanonicalizer $canonicalizer,
        private readonly SignatureVerifier $verifier
    ) {
    }

    public function enforce(Request $request, RouteRequirements $requirements, string $nonce, string $traceId): void
    {
        if (!$requirements->requiresSignature()) {
            return;
        }

        $context = $request->attribute('security_context');
        if (!$context instanceof SecurityContext) {
            throw new SecurityViolation('authentication_failed');
        }

        $kid = $request->header(ProtocolHeaders::KEY_ID);
        $signature = $request->header(ProtocolHeaders::SIGNATURE);

        if ($kid === null || $signature === null) {
            throw new SecurityViolation('missing_header');
        }

        $secret = $context->keyForKid($kid);
        if ($secret === null) {
            throw new SecurityViolation('signature_invalid');
        }

        $stringToSign = $this->builder->build($request, $nonce, $kid, $traceId);
        $canonical = $this->canonicalizer->canonicalize($stringToSign);

        if (!$this->verifier->verify($canonical, $secret, $signature)) {
            throw new SecurityViolation('signature_invalid');
        }
    }
}
