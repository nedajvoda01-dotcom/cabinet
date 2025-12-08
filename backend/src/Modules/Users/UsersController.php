<?php
declare(strict_types=1);

namespace Backend\Modules\Users;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * UsersController
 *
 * Endpoints:
 *  GET    /users/me
 *  GET    /users
 *  GET    /users/:id
 *  POST   /users
 *  PATCH  /users/:id
 *  DELETE /users/:id
 *  POST   /users/:id/roles/assign
 *  POST   /users/:id/roles/revoke
 *  POST   /users/:id/block
 *  POST   /users/:id/unblock
 */
final class UsersController
{
    public function __construct(private UsersService $service) {}

    public function meAction(Request $req): Response
    {
        try {
            $uid = $this->actorId($req);
            if (!$uid) throw new InvalidArgumentException("Unauthorized");

            $me = $this->service->me($uid);
            return Response::json(UsersSchemas::ok($me), 200);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 401);
        }
    }

    public function listAction(Request $req): Response
    {
        try {
            $dto = UsersSchemas::toListUsersDto($req->queryAll());
            $items = $this->service->list($dto);
            return Response::json(UsersSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail('Internal error'), 500);
        }
    }

    public function getAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $user = $this->service->get($uid);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 404);
        }
    }

    public function createAction(Request $req): Response
    {
        try {
            $dto = UsersSchemas::toCreateUserDto($req->json());
            $actorId = $this->actorId($req);

            $user = $this->service->create($dto, $actorId);
            return Response::json(UsersSchemas::ok($user), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function updateAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $dto = UsersSchemas::toUpdateUserDto($req->json());
            $actorId = $this->actorId($req);

            $user = $this->service->update($uid, $dto, $actorId);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function deleteAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $actorId = $this->actorId($req);

            $this->service->delete($uid, $actorId);
            return Response::json(UsersSchemas::ok(['deleted' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function assignRoleAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $dto = UsersSchemas::toRoleChangeDto($req->json());
            $actorId = $this->actorId($req);

            $user = $this->service->assignRole($uid, $dto, $actorId);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function revokeRoleAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $dto = UsersSchemas::toRoleChangeDto($req->json());
            $actorId = $this->actorId($req);

            $user = $this->service->revokeRole($uid, $dto, $actorId);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function blockAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $actorId = $this->actorId($req);
            $reason = ($req->json()['reason'] ?? 'blocked');

            $user = $this->service->block($uid, $actorId, (string)$reason);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    public function unblockAction(Request $req, string $id): Response
    {
        try {
            $uid = UsersSchemas::toIdDto($id)['id'];
            $actorId = $this->actorId($req);

            $user = $this->service->unblock($uid, $actorId);
            return Response::json(UsersSchemas::ok($user), 200);
        } catch (Throwable $e) {
            return Response::json(UsersSchemas::fail($e->getMessage()), 409);
        }
    }

    private function actorId(Request $req): ?int
    {
        $id = $req->context('user_id');
        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int)$id : null);
    }
}
