<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * PhotosController
 *
 * Endpoints:
 *  POST /photos/run
 *  GET  /photos/tasks
 *  GET  /photos/tasks/:id
 *  POST /photos/tasks/:id/retry
 *  POST /photos/webhook
 *
 *  GET  /photos/card/:card_id
 *  DELETE /photos/:id
 *  POST /photos/card/:card_id/primary
 */
final class PhotosController
{
    public function __construct(private PhotosService $service) {}

    // -------- Tasks --------

    public function runAction(Request $req): Response
    {
        try {
            $dto = PhotosSchemas::toRunPhotosDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->run($dto, $actorId);
            return Response::json(PhotosSchemas::ok($task), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 409);
        }
    }

    public function listTasksAction(Request $req): Response
    {
        try {
            $dto = PhotosSchemas::toListPhotoTasksDto($req->queryAll());
            $items = $this->service->listTasks($dto);
            return Response::json(PhotosSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail('Internal error'), 500);
        }
    }

    public function getTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = PhotosSchemas::toTaskIdDto($id)['id'];
            $task = $this->service->getTask($tid);
            return Response::json(PhotosSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 404);
        }
    }

    public function retryTaskAction(Request $req, string $id): Response
    {
        try {
            $tid = PhotosSchemas::toTaskIdDto($id)['id'];
            $dto = PhotosSchemas::toRetryDto($req->json());
            $actorId = $this->actorId($req);

            $task = $this->service->retryTask($tid, $dto, $actorId);
            return Response::json(PhotosSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 409);
        }
    }

    public function webhookAction(Request $req): Response
    {
        try {
            $dto = PhotosSchemas::toWebhookDto($req->json());
            $task = $this->service->webhook($dto);
            return Response::json(PhotosSchemas::ok($task), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 409);
        }
    }

    // -------- Photos artifacts --------

    public function listCardPhotosAction(Request $req, string $cardId): Response
    {
        try {
            $cid = PhotosSchemas::toTaskIdDto($cardId)['id']; // валидатор id>0
            $items = $this->service->listCardPhotos($cid);
            return Response::json(PhotosSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail('Internal error'), 500);
        }
    }

    public function deletePhotoAction(Request $req, string $id): Response
    {
        try {
            $pid = PhotosSchemas::toPhotoIdDto($id)['id'];
            $actorId = $this->actorId($req);

            $this->service->deletePhoto($pid, $actorId);
            return Response::json(PhotosSchemas::ok(['deleted' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail('Internal error'), 500);
        }
    }

    public function setPrimaryAction(Request $req, string $cardId): Response
    {
        try {
            $cid = PhotosSchemas::toTaskIdDto($cardId)['id'];
            $dto = PhotosSchemas::toSetPrimaryDto($req->json());
            $actorId = $this->actorId($req);

            $photos = $this->service->setPrimary($cid, $dto['photo_id'], $actorId);
            return Response::json(PhotosSchemas::ok($photos), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(PhotosSchemas::fail($e->getMessage()), 409);
        }
    }

    private function actorId(Request $req): ?int
    {
        $id = $req->context('user_id');
        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int)$id : null);
    }
}
