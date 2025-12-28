<?php
declare(strict_types=1);

namespace Backend\Application\Contracts;

final class Error
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $code,
        public ErrorKind $kind,
        public string $message,
        public array $details = [],
        public TraceContext $traceContext = new TraceContext('')
    ) {
        if ($this->traceContext->traceId() === '') {
            $this->traceContext = TraceContext::ensure();
        }
    }

    public static function fromMessage(
        string $code,
        ErrorKind $kind,
        string $message,
        array $details = [],
        ?TraceContext $traceContext = null
    ): self {
        return new self($code, $kind, $message, $details, $traceContext ?? TraceContext::ensure());
    }

    public static function fromThrowable(\Throwable $e, ?TraceContext $traceContext = null): self
    {
        return new self(
            $e->getCode() ? (string) $e->getCode() : 'internal_error',
            ErrorKind::UNKNOWN,
            $e->getMessage(),
            ['class' => $e::class],
            $traceContext ?? TraceContext::ensure(),
        );
    }

    /**
     * @return array{code:string,kind:string,message:string,details:array,traceId:string}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'kind' => $this->kind->value,
            'message' => $this->message,
            'details' => $this->details,
            'traceId' => $this->traceContext->traceId(),
        ];
    }
}
