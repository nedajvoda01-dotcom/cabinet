<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Users;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;

enum AccessRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}

final class AccessRequest
{
    private AccessRequestStatus $status;

    private function __construct(
        private readonly AccessRequestId $id,
        private readonly UserId $userId,
        AccessRequestStatus $status
    ) {
        $this->status = $status;
    }

    public static function create(AccessRequestId $id, UserId $userId): self
    {
        return new self($id, $userId, AccessRequestStatus::PENDING);
    }

    public function id(): AccessRequestId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function status(): AccessRequestStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === AccessRequestStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === AccessRequestStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === AccessRequestStatus::REJECTED;
    }

    public function approve(): void
    {
        if (!$this->isPending()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                AccessRequestStatus::APPROVED->value,
                'AccessRequest'
            );
        }

        $this->status = AccessRequestStatus::APPROVED;
    }

    public function reject(): void
    {
        if (!$this->isPending()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                AccessRequestStatus::REJECTED->value,
                'AccessRequest'
            );
        }

        $this->status = AccessRequestStatus::REJECTED;
    }
}
