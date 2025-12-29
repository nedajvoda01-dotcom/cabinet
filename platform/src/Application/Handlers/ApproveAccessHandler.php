<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Access\ApproveAccessCommand;
use Cabinet\Backend\Application\Ports\AccessRequestRepository;
use Cabinet\Backend\Application\Ports\IdGenerator;
use Cabinet\Backend\Application\Ports\UserRepository;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Application\Observability\AuditLogger;
use Cabinet\Backend\Application\Observability\AuditEvent;
use Cabinet\Backend\Domain\Shared\ValueObject\HierarchyRole;
use Cabinet\Backend\Domain\Shared\ValueObject\ScopeSet;
use Cabinet\Backend\Domain\Users\AccessRequestId;
use Cabinet\Backend\Domain\Users\User;
use Cabinet\Backend\Domain\Users\UserId;

/**
 * @implements CommandHandler<string>
 */
final class ApproveAccessHandler implements CommandHandler
{
    public function __construct(
        private readonly AccessRequestRepository $accessRequestRepository,
        private readonly UserRepository $userRepository,
        private readonly IdGenerator $idGenerator,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function handle(Command $command): Result
    {
        if (!$command instanceof ApproveAccessCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $accessRequestId = AccessRequestId::fromString($command->accessRequestId());
        $accessRequest = $this->accessRequestRepository->findById($accessRequestId);

        if ($accessRequest === null) {
            return Result::failure(ApplicationError::notFound('Access request not found'));
        }

        try {
            $accessRequest->approve();
        } catch (\Exception $e) {
            // Audit: ApproveAccess rejected
            $auditEvent = new AuditEvent(
                id: $this->idGenerator->generate(),
                ts: (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
                action: 'access.approve.rejected',
                targetType: 'access_request',
                targetId: $accessRequestId->toString(),
                data: ['error' => $e->getMessage()]
            );
            $this->auditLogger->record($auditEvent);
            
            return Result::failure(ApplicationError::invalidState($e->getMessage()));
        }

        $this->accessRequestRepository->save($accessRequest);

        // Create User entity with default role and minimal scopes
        $userId = UserId::fromString($this->idGenerator->generate());
        $role = HierarchyRole::fromString('user');
        $scopes = ScopeSet::empty();
        $timestamp = time();

        $user = User::create($userId, $role, $scopes, $timestamp);
        $this->userRepository->save($user);

        // Audit: ApproveAccess approved
        $auditEvent = new AuditEvent(
            id: $this->idGenerator->generate(),
            ts: (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
            action: 'access.approve.approved',
            targetType: 'access_request',
            targetId: $accessRequestId->toString(),
            data: ['user_id' => $userId->toString()]
        );
        $this->auditLogger->record($auditEvent);

        return Result::success($userId->toString());
    }
}
