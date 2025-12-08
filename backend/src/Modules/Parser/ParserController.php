<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * ParserController
 *
 * Endpoints:
 *  POST /parser/run
 *  GET  /parser/tasks
 *  GET  /parser/tasks/:id
 *  POST /parser/tasks/:id/retry
 *  POST /parser/webhook
 */
final class ParserController
{
    public function __construct(private ParserService $service) {}

    public function runAction(Request $req): Response
    {
        try {
            $dto = ParserSchemas::toRunParserDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->run($dto, $actorId);
            return Response::json(ParserSchemas::ok($task), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 409);
        }
    }

    public function listTasksAction(Request $req): Response
    {
        try {
            $dto = ParserSchemas::toListParserTasksDto($req->queryAll());
            $items = $this->service->listTasks($dto);
            return Response::json(ParserSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ParserSchemas::fail('Internal error'), 500);
        }
    }

    public function getTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = ParserSchemas::toTaskIdDto($id)['id'];
            $task = $this->service->getTask($tid);
            return Response::json(ParserSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 404);
        }
    }

    public function retryTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = ParserSchemas::toTaskIdDto($id)['id'];
            $dto = ParserSchemas::toRetryDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->retryTask($tid, $dto, $actorId);
            return Response::json(ParserSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 409);
        }
    }

    public function webhookAction(Request $req): Response
    {
        try {
            $dto = ParserSchemas::toWebhookDto($req->json());
            $task = $this->service->webhook($dto);
            return Response::json(ParserSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ParserSchemas::fail($e->getMessage()), 409);
        }
    }

    private function actorId(Request $req): ?int
    {
        $id = $req->context('user_id');
        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int)$id : null);
    }
}
