<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

final class Actor
{
    public function __construct(public readonly string $actorId, public readonly ActorType $actorType)
    {
        if ($actorId === null) {
            throw new \InvalidArgumentException('actorId is required.');
        }
        new ActorId((string) $actorId);
        if ($actorType === null) {
            throw new \InvalidArgumentException('actorType is required.');
        }
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->actorId !== null) {
            $data['actorId'] = $this->actorId;
        }
        if ($this->actorType !== null) {
            $data['actorType'] = $this->actorType;
        }
        return $data;
    }
}
