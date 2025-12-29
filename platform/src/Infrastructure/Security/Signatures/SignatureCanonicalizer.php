<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Signatures;

use Cabinet\Contracts\CanonicalJson;

final class SignatureCanonicalizer
{
    public function canonicalize(array $stringToSign): string
    {
        return CanonicalJson::encode($stringToSign);
    }
}
