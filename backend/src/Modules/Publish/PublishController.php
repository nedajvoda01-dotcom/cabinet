<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * PublishController
 *
 * Endpoints:
 *  POST /publish/run
 *  GET  /publish/tasks
 *  GET  /publish/tasks/:id
 *  POST /publish/tasks/:id/cancel
 *  POST /publish/tasks/:id/retry
 *  POST /publish/webhook
 *  GET  /publish/metrics
 */
final class PublishController
{
    public function __construct(private PublishService $service) {}

    public function runAction(Request $req): Response
    {
        try {
            $dto = PublishSchemas::toRunPublishDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->run($dto, $actorId);
            return Response::json(PublishSchemas::ok($task), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 409);
        }
    }

    public function listTasksAction(Request $req): Response
    {
        try {
            $dto = PublishSchemas::toListPublishTasksDto($req->queryAll());
            $items = $this->service->listTasks($dto);
            return Response::json(PublishSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail('Internal error'), 500);
        }
    }

    public function getTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = PublishSchemas::toTaskIdDto($id)['id'];
            $task = $this->service->getTask($tid);
            return Response::json(PublishSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 404);
        }
    }

    public function cancelTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = PublishSchemas::toTaskIdDto($id)['id'];
            $dto = PublishSchemas::toCancelDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->cancelTask($tid, $dto, $actorId);
            return Response::json(PublishSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 409);
        }
    }

    public function retryTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = PublishSchemas::toTaskIdDto($id)['id'];
            $dto = PublishSchemas::toRetryDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->retryTask($tid, $dto, $actorId);
            return Response::json(PublishSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 409);
        }
    }

    public function webhookAction(Request $req): Response
    {
        try {
            $dto = PublishSchemas::toWebhookDto($req->json());
            $task = $this->service->webhook($dto);
            return Response::json(PublishSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 409);
        }
    }

    public function metricsAction(Request $req): Response
    {
        try {
            $dto = PublishSchemas::toMetricsDto($req->queryAll());
            $data = $this->service->metrics($dto);
            return Response::json(PublishSchemas::ok($data), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PublishSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PublishSchemas::fail('Internal error'), 500);
        }
    }

    private function actorId(Request $req): ?int
    {
        $id = $req->context('user_id');
        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int)$id : null);
    }
}
