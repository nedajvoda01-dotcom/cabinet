<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * ExportController
 *
 * Endpoints:
 *  POST /export
 *  GET  /export
 *  GET  /export/:id
 *  POST /export/:id/cancel
 *  POST /export/:id/retry
 *  GET  /export/:id/download
 */
final class ExportController
{
    public function __construct(private ExportService $service) {}

    public function createExportAction(Request $req): Response
    {
        try {
            $dto = ExportSchemas::toCreateExportDto($req->json());
            $actorId = $this->actorId($req);

            $export = $this->service->createExport($dto, $actorId);
            return Response::json(ExportSchemas::ok($export), 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 500);
        }
    }

    public function listExportsAction(Request $req): Response
    {
        try {
            $dto = ExportSchemas::toListExportsDto($req->queryAll());
            $items = $this->service->listExports($dto);
            return Response::json(ExportSchemas::ok($items), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail('Internal error'), 500);
        }
    }

    public function getExportAction(Request $req, string $id): Response
    {
        try {
            $eid = ExportSchemas::toExportIdDto($id)['id'];
            $export = $this->service->getExport($eid);
            return Response::json(ExportSchemas::ok($export), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 404);
        }
    }

    public function cancelExportAction(Request $req, string $id): Response
    {
        try {
            $eid = ExportSchemas::toExportIdDto($id)['id'];
            $dto = ExportSchemas::toCancelDto($req->json());
            $actorId = $this->actorId($req);

            $export = $this->service->cancelExport($eid, $dto, $actorId);
            return Response::json(ExportSchemas::ok($export), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 409);
        }
    }

    public function retryExportAction(Request $req, string $id): Response
    {
        try {
            $eid = ExportSchemas::toExportIdDto($id)['id'];
            $dto = ExportSchemas::toRetryDto($req->json());
            $actorId = $this->actorId($req);

            $export = $this->service->retryExport($eid, $dto, $actorId);
            return Response::json(ExportSchemas::ok($export), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 409);
        }
    }

    public function downloadExportAction(Request $req, string $id): Response
    {
        try {
            $eid = ExportSchemas::toExportIdDto($id)['id'];
            $info = $this->service->downloadInfo($eid);
            return Response::json(ExportSchemas::ok($info), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(ExportSchemas::fail($e->getMessage()), 409);
        }
    }

    private function actorId(Request $req): int
    {
        $id = $req->context('user_id');
        if (is_int($id)) return $id;
        if (is_string($id) && ctype_digit($id)) return (int)$id;
        // по умолчанию системный пользователь
        return 0;
    }
}
