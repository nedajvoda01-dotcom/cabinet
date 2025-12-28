<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Shared;

enum ApplicationErrorCode: string
{
    case NOT_FOUND = 'not_found';
    case INVALID_STATE = 'invalid_state';
    case PERMISSION_DENIED = 'permission_denied';
    case IDEMPOTENCY_CONFLICT = 'idempotency_conflict';
    case VALIDATION_ERROR = 'validation_error';
}

final class ApplicationError
{
    private function __construct(
        private readonly ApplicationErrorCode $code,
        private readonly string $message
    ) {
    }

    public static function notFound(string $message): self
    {
        return new self(ApplicationErrorCode::NOT_FOUND, $message);
    }

    public static function invalidState(string $message): self
    {
        return new self(ApplicationErrorCode::INVALID_STATE, $message);
    }

    public static function permissionDenied(string $message): self
    {
        return new self(ApplicationErrorCode::PERMISSION_DENIED, $message);
    }

    public static function idempotencyConflict(string $message): self
    {
        return new self(ApplicationErrorCode::IDEMPOTENCY_CONFLICT, $message);
    }

    public static function validationError(string $message): self
    {
        return new self(ApplicationErrorCode::VALIDATION_ERROR, $message);
    }

    public function code(): ApplicationErrorCode
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }
}
