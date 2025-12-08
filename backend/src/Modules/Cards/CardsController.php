<?php
declare(strict_types=1);

namespace Backend\Modules\Cards;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * CardsController
 *
 * Endpoints:
 *  GET    /cards
 *  GET    /cards/:id
 *  POST   /cards
 *  PATCH  /cards/:id
 *  DELETE /cards/:id
 *
 *  POST   /cards/:id/transition
 *  POST   /cards/bulk-transition
 *
 *  POST   /cards/:id/retry
 */
final class CardsController
{
    public function __construct(private CardsService $service) {}

    // -------- CRUD --------

    public function listCardsAction(Request $req): Response
    {
        try {
            $dto = CardsSchemas::toListCardsDto($req->queryAll());
            $items = $this->service->listCards($dto);
            return Response::json(CardsSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail('Internal error'), 500);
        }
    }

    public function getCardAction(Request $req, string $id): Response
    {
        try {
            $cid = CardsSchemas::toCardIdDto($id)['id'];
            $card = $this->service->getCard($cid);
            return Response::json(CardsSchemas::ok($card), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 404);
        }
    }

    public function createCardAction(Request $req): Response
    {
        try {
            $dto = CardsSchemas::toCreateCardDto($req->json());
            $actorId = $this->actorId($req);
            $card = $this->service->createCard($dto, $actorId);
            return Response::json(CardsSchemas::ok($card), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 500);
        }
    }

    public function updateCardAction(Request $req, string $id): Response
    {
        try {
            $cid = CardsSchemas::toCardIdDto($id)['id'];
            $dto = CardsSchemas::toUpdateCardDto($req->json());
            $actorId = $this->actorId($req);

            $card = $this->service->updateCard($cid, $dto, $actorId);
            return Response::json(CardsSchemas::ok($card), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 500);
        }
    }

    public function deleteCardAction(Request $req, string $id): Response
    {
        try {
            $cid = CardsSchemas::toCardIdDto($id)['id'];
            $actorId = $this->actorId($req);

            $this->service->deleteCard($cid, $actorId);
            return Response::json(CardsSchemas::ok(['deleted' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 500);
        }
    }

    // -------- Transitions --------

    public function transitionCardAction(Request $req, string $id): Response
    {
        try {
            $cid = CardsSchemas::toCardIdDto($id)['id'];
            $dto = CardsSchemas::toTransitionDto($req->json());
            $actorId = $this->actorId($req);

            $card = $this->service->transitionCard($cid, $dto, $actorId);
            return Response::json(CardsSchemas::ok($card), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 409);
        }
    }

    public function bulkTransitionAction(Request $req): Response
    {
        try {
            $dto = CardsSchemas::toBulkTransitionDto($req->json());
            $actorId = $this->actorId($req);

            $result = $this->service->bulkTransition($dto, $actorId);
            return Response::json(CardsSchemas::ok($result), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 500);
        }
    }

    // -------- Retry --------

    public function retryCardAction(Request $req, string $id): Response
    {
        try {
            $cid = CardsSchemas::toCardIdDto($id)['id'];
            $dto = CardsSchemas::toRetryDto($req->json());
            $actorId = $this->actorId($req);

            $res = $this->service->retryCard($cid, $dto, $actorId);
            return Response::json(CardsSchemas::ok($res), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(CardsSchemas::fail($e->getMessage()), 409);
        }
    }

    // -------- helpers --------

    private function actorId(Request $req): ?int
    {
        // В вашем проекте это может быть $req->user()->id или payload JWT.
        // Оставляем универсально:
        $id = $req->context('user_id');
        return is_int($id) ? $id : (is_string($id) && ctype_digit($id) ? (int)$id : null);
    }
}
