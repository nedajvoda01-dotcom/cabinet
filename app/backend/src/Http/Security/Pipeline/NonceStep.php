<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Validation\Protocol\NonceFormatValidator;
use Cabinet\Backend\Infrastructure\Security\Nonce\NonceRepository;

final class NonceStep
{
    private NonceRepository $repository;

    private NonceFormatValidator $validator;

    public function __construct(NonceRepository $repository, NonceFormatValidator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function enforce(Request $request, RouteRequirements $requirements): string
    {
        if (!$requirements->requiresNonce()) {
            return '';
        }

        $nonce = $request->header(ProtocolHeaders::NONCE);
        if ($nonce === null) {
            throw new SecurityViolation('missing_header');
        }

        if (!$this->validator->isValid($nonce)) {
            throw new SecurityViolation('nonce_invalid');
        }

        if (!$this->repository->consume($nonce)) {
            throw new SecurityViolation('nonce_reuse');
        }

        return $nonce;
    }
}
