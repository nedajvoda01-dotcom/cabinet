<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Access\RequestAccessCommand;
use Cabinet\Backend\Application\Ports\AccessRequestRepository;
use Cabinet\Backend\Application\Ports\IdGenerator;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Users\AccessRequest;
use Cabinet\Backend\Domain\Users\AccessRequestId;
use Cabinet\Backend\Domain\Users\UserId;

/**
 * @implements CommandHandler<string>
 */
final class RequestAccessHandler implements CommandHandler
{
    public function __construct(
        private readonly AccessRequestRepository $accessRequestRepository,
        private readonly IdGenerator $idGenerator
    ) {
    }

    public function handle(Command $command): Result
    {
        if (!$command instanceof RequestAccessCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $accessRequestId = AccessRequestId::fromString($this->idGenerator->generate());
        $userId = UserId::fromString($command->requestedBy());

        $accessRequest = AccessRequest::create($accessRequestId, $userId);
        $this->accessRequestRepository->save($accessRequest);

        return Result::success($accessRequestId->toString());
    }
}
