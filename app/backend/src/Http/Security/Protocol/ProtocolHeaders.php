<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Protocol;

final class ProtocolHeaders
{
    public const TRACE = 'x-cabinet-trace-id';
    public const NONCE = 'x-cabinet-nonce';
    public const IDEMPOTENCY_KEY = 'x-cabinet-idempotency-key';
    public const KEY_ID = 'x-cabinet-key-id';
    public const SIGNATURE = 'x-cabinet-signature';
    public const ENCRYPTION = 'x-cabinet-encryption';
    public const KEY_EXCHANGE = 'x-cabinet-key-exchange';
    public const ACTOR = 'x-cabinet-actor';
}
