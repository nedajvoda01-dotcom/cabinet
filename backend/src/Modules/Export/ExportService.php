<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use RuntimeException;

/**
 * ExportService
 *
 * Бизнес-логика экспорта:
 *  - создание export job
 *  - отмена/ретрай
 *  - просмотр статуса
 */
final class ExportService
{
    // Разрешённые статусы экспортов
    private const STATUSES = ['queued','running','done','failed','canceled'];

    public function __construct(
        private ExportModel $model,
        private ExportJobs $jobs
    ) {}

    public function createExport(array $dto, int $actorUserId): array
    {
        $export = $this->model->createExport(
            $actorUserId,
            $dto['type'],
            $dto['format'],
            $dto['params'] ?? []
        );

        $this->jobs->dispatchExportRun((int)$export['id']);
        $this->model->writeAudit($actorUserId, 'export_create', "Export #{$export['id']} created ({$dto['type']})");

        return $export;
    }

    public function listExports(array $dto): array
    {
        return $this->model->listExports($dto);
    }

    public function getExport(int $id): array
    {
        return $this->model->getExportById($id);
    }

    public function cancelExport(int $id, array $dto, int $actorUserId): array
    {
        $export = $this->model->getExportById($id);

        if (in_array($export['status'], ['done','failed','canceled'], true)) {
            throw new RuntimeException("Export already finished");
        }

        $updated = $this->model->updateExportStatus($id, 'canceled', 'manual_cancel', $dto['reason']);
        $this->model->writeAudit($actorUserId, 'export_cancel', "Export #{$id} canceled ({$dto['reason']})");

        return $updated;
    }

    public function retryExport(int $id, array $dto, int $actorUserId): array
    {
        $export = $this->model->getExportById($id);

        if ($export['status'] === 'running') {
            throw new RuntimeException("Export is running");
        }
        if ($export['status'] === 'done' && empty($dto['force'])) {
            throw new RuntimeException("Export already done (use force to retry)");
        }

        $updated = $this->model->updateExportStatus($id, 'queued', null, null);
        $this->jobs->dispatchExportRetry($id, $dto['reason'], (bool)$dto['force']);
        $this->model->writeAudit($actorUserId, 'export_retry', "Export #{$id} retry requested ({$dto['reason']})");

        return $updated;
    }

    /**
     * Возвращает информацию для скачивания.
     * Реальный signed-url можно генерить в модели/адаптере.
     */
    public function downloadInfo(int $id): array
    {
        $export = $this->model->getExportById($id);

        if ($export['status'] !== 'done') {
            throw new RuntimeException("Export not ready");
        }

        return [
            'id' => (int)$export['id'],
            'file_path' => $export['file_path'] ?? null,
            'download_url' => $export['file_url'] ?? null,
            'format' => $export['format'] ?? null,
        ];
    }
}
