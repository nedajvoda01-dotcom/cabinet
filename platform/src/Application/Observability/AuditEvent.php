<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Observability;

final class AuditEvent
{
    private string $id;
    private string $ts;
    private ?string $actorId;
    private ?string $actorType;
    private ?string $requestId;
    private string $action;
    private string $targetType;
    private string $targetId;
    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        string $id,
        string $ts,
        string $action,
        string $targetType,
        string $targetId,
        array $data = [],
        ?string $actorId = null,
        ?string $actorType = null,
        ?string $requestId = null
    ) {
        $this->id = $id;
        $this->ts = $ts;
        $this->actorId = $actorId;
        $this->actorType = $actorType;
        $this->requestId = $requestId;
        $this->action = $action;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->data = $data;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function ts(): string
    {
        return $this->ts;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function actorType(): ?string
    {
        return $this->actorType;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function targetType(): string
    {
        return $this->targetType;
    }

    public function targetId(): string
    {
        return $this->targetId;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
