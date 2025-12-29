<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\AccessRequestRepository;
use Cabinet\Backend\Domain\Users\AccessRequest;
use Cabinet\Backend\Domain\Users\AccessRequestId;
use Cabinet\Backend\Domain\Users\AccessRequestStatus;
use Cabinet\Backend\Domain\Users\UserId;
use PDO;

final class AccessRequestsRepository implements AccessRequestRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(AccessRequest $accessRequest): void
    {
        $sql = <<<SQL
        INSERT INTO access_requests (id, requested_by, status, requested_at, resolved_at, resolved_by)
        VALUES (:id, :requested_by, :status, :requested_at, :resolved_at, :resolved_by)
        ON CONFLICT(id) DO UPDATE SET
            status = :status,
            resolved_at = :resolved_at,
            resolved_by = :resolved_by
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $accessRequest->id()->toString(),
            ':requested_by' => $accessRequest->userId()->toString(),
            ':status' => $accessRequest->status()->value,
            ':requested_at' => date('Y-m-d H:i:s'),
            ':resolved_at' => null,
            ':resolved_by' => null,
        ]);
    }

    public function findById(AccessRequestId $id): ?AccessRequest
    {
        $sql = 'SELECT * FROM access_requests WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return null;
        }

        return $this->hydrateAccessRequest($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateAccessRequest(array $row): AccessRequest
    {
        $accessRequest = AccessRequest::create(
            AccessRequestId::fromString($row['id']),
            UserId::fromString($row['requested_by'])
        );

        // If status is not pending, we need to apply the state change
        $status = AccessRequestStatus::from($row['status']);
        if ($status === AccessRequestStatus::APPROVED) {
            $accessRequest->approve();
        } elseif ($status === AccessRequestStatus::REJECTED) {
            $accessRequest->reject();
        }

        return $accessRequest;
    }
}
