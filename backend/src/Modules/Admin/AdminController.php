<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * AdminController
 *
 * Реализует Admin endpoints из Spec:
 *  Queues:
 *    GET  /admin/queues
 *    GET  /admin/queues/:type/jobs
 *    POST /admin/queues/:type/pause
 *    POST /admin/queues/:type/resume
 *
 *  DLQ:
 *    GET  /admin/dlq
 *    GET  /admin/dlq/:id
 *    POST /admin/dlq/:id/retry
 *    POST /admin/dlq/bulk-retry
 *
 *  System:
 *    GET  /admin/health
 *    GET  /admin/logs
 *    GET  /admin/users
 *    POST /admin/users/:id/roles
 */
final class AdminController
{
    public function __construct(private AdminService $service) {}

    // -------------- Queues -------------

    public function listQueuesAction(Request $req): Response
    {
        try {
            $dto = AdminSchemas::toListQueuesDto($req->queryAll());
            $items = $this->service->listQueues($dto);
            return Response::json(AdminSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function listQueueJobsAction(Request $req, string $type): Response
    {
        try {
            $dto = AdminSchemas::toListQueueJobsDto($type, $req->queryAll());
            $items = $this->service->listQueueJobs($dto);
            return Response::json(AdminSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function pauseQueueAction(Request $req, string $type): Response
    {
        try {
            AdminSchemas::toQueueTypeDto($type);
            $this->service->pauseQueue($type);
            return Response::json(AdminSchemas::ok(['type' => $type, 'paused' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function resumeQueueAction(Request $req, string $type): Response
    {
        try {
            AdminSchemas::toQueueTypeDto($type);
            $this->service->resumeQueue($type);
            return Response::json(AdminSchemas::ok(['type' => $type, 'paused' => false]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    // -------------- DLQ -------------

    public function listDlqAction(Request $req): Response
    {
        try {
            $dto = AdminSchemas::toListDlqDto($req->queryAll());
            $items = $this->service->listDlq($dto);
            return Response::json(AdminSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function getDlqItemAction(Request $req, string $id): Response
    {
        try {
            $dto = AdminSchemas::toDlqIdDto($id);
            $item = $this->service->getDlqItem($dto['id']);
            return Response::json(AdminSchemas::ok($item), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 404);
        }
    }

    public function retryDlqItemAction(Request $req, string $id): Response
    {
        try {
            $dto = AdminSchemas::toDlqIdDto($id);
            $this->service->retryDlqItem($dto['id']);
            return Response::json(AdminSchemas::ok(['id' => $dto['id'], 'status' => 'retrying']), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function bulkRetryDlqAction(Request $req): Response
    {
        try {
            $body = $req->json();
            $dto = AdminSchemas::toBulkRetryDlqDto($body);
            $count = $this->service->bulkRetryDlq($dto);
            return Response::json(AdminSchemas::ok(['retried' => $count]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    // -------------- System -------------

    public function healthAction(Request $req): Response
    {
        try {
            $state = $this->service->health();
            return Response::json(AdminSchemas::ok($state), 200);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function logsAction(Request $req): Response
    {
        try {
            $dto = AdminSchemas::toListLogsDto($req->queryAll());
            $items = $this->service->logs($dto);
            return Response::json(AdminSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    // -------------- Users / Roles -------------

    public function listUsersAction(Request $req): Response
    {
        try {
            $dto = AdminSchemas::toListUsersDto($req->queryAll());
            $items = $this->service->listUsers($dto);
            return Response::json(AdminSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }

    public function updateUserRolesAction(Request $req, string $id): Response
    {
        try {
            $userId = AdminSchemas::toDlqIdDto($id)['id']; // тот же валидатор id>0
            $dto = AdminSchemas::toUpdateUserRolesDto($req->json());

            $roles = $this->service->updateUserRoles($userId, $dto['roles']);

            return Response::json(AdminSchemas::ok([
                'user_id' => $userId,
                'roles' => $roles,
            ]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AdminSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AdminSchemas::fail('Internal error'), 500);
        }
    }
}
