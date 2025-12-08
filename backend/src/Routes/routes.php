<?php
declare(strict_types=1);

use Backend\Modules\Admin\AdminController;
use Backend\Modules\Auth\AuthController;
use Backend\Modules\Cards\CardsController;
use Backend\Modules\Export\ExportController;
use Backend\Modules\Parser\ParserController;
use Backend\Modules\Photos\PhotosController;
use Backend\Modules\Publish\PublishController;
use Modules\Robot\RobotController;
use Backend\Modules\Users\UsersController;

/**
 * routes.php
 *
 * Единая карта API приложения.
 *
 * Ожидается, что $router предоставляет методы:
 *  - get($path, $handler)
 *  - post($path, $handler)
 *  - patch($path, $handler)
 *  - delete($path, $handler)
 *  - group($prefix, callable $fn)
 *
 * $handler: [ControllerClass::class, 'methodAction']
 *
 * Если сигнатура роутера отличается — адаптируй только этот файл.
 */
return static function ($router): void {

    // -----------------------------------------------------------
    // API prefix / versioning
    // Если не нужен префикс /api — можно удалить group и
    // оставить все маршруты на корне.
    // -----------------------------------------------------------
    $router->group('/api', function ($r) {

        // -------------------------
        // Auth
        // -------------------------
        $r->group('/auth', function ($rr) {
            $rr->post('/login',   [AuthController::class, 'loginAction']);
            $rr->post('/logout',  [AuthController::class, 'logoutAction']);
            $rr->post('/refresh', [AuthController::class, 'refreshAction']);
            $rr->get('/me',       [AuthController::class, 'meAction']);
        });

        // -------------------------
        // Users (RBAC)
        // -------------------------
        $r->group('/users', function ($rr) {
            $rr->get('/me',     [UsersController::class, 'meAction']);
            $rr->get('',        [UsersController::class, 'listAction']);
            $rr->get('/:id',    [UsersController::class, 'getAction']);
            $rr->post('',       [UsersController::class, 'createAction']);
            $rr->patch('/:id',  [UsersController::class, 'updateAction']);
            $rr->delete('/:id', [UsersController::class, 'deleteAction']);

            $rr->post('/:id/roles/assign', [UsersController::class, 'assignRoleAction']);
            $rr->post('/:id/roles/revoke', [UsersController::class, 'revokeRoleAction']);

            $rr->post('/:id/block',   [UsersController::class, 'blockAction']);
            $rr->post('/:id/unblock', [UsersController::class, 'unblockAction']);
        });

        // -------------------------
        // Cards
        // -------------------------
        $r->group('/cards', function ($rr) {
            $rr->get('',        [CardsController::class, 'listCardsAction']);
            $rr->get('/:id',    [CardsController::class, 'getCardAction']);
            $rr->post('',       [CardsController::class, 'createCardAction']);
            $rr->patch('/:id',  [CardsController::class, 'updateCardAction']);
            $rr->delete('/:id', [CardsController::class, 'deleteCardAction']);

            // Pipeline triggers (если в CardsController названы иначе — меняем ТОЛЬКО тут)
            $rr->post('/:id/parse',   [CardsController::class, 'parseCardAction']);    // -> Parser.run
            $rr->post('/:id/photos',  [CardsController::class, 'photosCardAction']);   // -> Photos.run
            $rr->post('/:id/publish', [CardsController::class, 'publishCardAction']);  // -> Publish.run
        });

        // -------------------------
        // Export
        // -------------------------
        $r->group('/export', function ($rr) {
            $rr->post('',             [ExportController::class, 'createExportAction']);
            $rr->get('',              [ExportController::class, 'listExportsAction']);
            $rr->get('/:id',          [ExportController::class, 'getExportAction']);
            $rr->post('/:id/cancel',  [ExportController::class, 'cancelExportAction']);
            $rr->post('/:id/retry',   [ExportController::class, 'retryExportAction']);
            $rr->get('/:id/download', [ExportController::class, 'downloadExportAction']);
        });

        // -------------------------
        // Parser
        // -------------------------
        $r->group('/parser', function ($rr) {
            $rr->post('/run',             [ParserController::class, 'runAction']);
            $rr->get('/tasks',            [ParserController::class, 'listTasksAction']);
            $rr->get('/tasks/:id',        [ParserController::class, 'getTaskAction']);
            $rr->post('/tasks/:id/retry', [ParserController::class, 'retryTaskAction']);
            $rr->post('/webhook',         [ParserController::class, 'webhookAction']);
        });

        // -------------------------
        // Photos
        // -------------------------
        $r->group('/photos', function ($rr) {
            $rr->post('/run',             [PhotosController::class, 'runAction']);
            $rr->get('/tasks',            [PhotosController::class, 'listTasksAction']);
            $rr->get('/tasks/:id',        [PhotosController::class, 'getTaskAction']);
            $rr->post('/tasks/:id/retry', [PhotosController::class, 'retryTaskAction']);
            $rr->post('/webhook',         [PhotosController::class, 'webhookAction']);

            // artifacts
            $rr->get('/card/:card_id',          [PhotosController::class, 'listCardPhotosAction']);
            $rr->delete('/:id',                [PhotosController::class, 'deletePhotoAction']);
            $rr->post('/card/:card_id/primary', [PhotosController::class, 'setPrimaryAction']);
        });

        // -------------------------
        // Publish
        // -------------------------
        $r->group('/publish', function ($rr) {
            $rr->post('/run',              [PublishController::class, 'runAction']);
            $rr->get('/tasks',             [PublishController::class, 'listTasksAction']);
            $rr->get('/tasks/:id',         [PublishController::class, 'getTaskAction']);
            $rr->post('/tasks/:id/cancel', [PublishController::class, 'cancelTaskAction']);
            $rr->post('/tasks/:id/retry',  [PublishController::class, 'retryTaskAction']);
            $rr->post('/webhook',          [PublishController::class, 'webhookAction']);
            $rr->get('/metrics',           [PublishController::class, 'metricsAction']);
        });

        // -------------------------
        // Robot (internal/admin)
        // -------------------------
        $r->group('/robot', function ($rr) {
            $rr->get('/health',           [RobotController::class, 'health']);
            $rr->get('/runs/:id',         [RobotController::class, 'getRun']);
            $rr->post('/runs/:id/retry',  [RobotController::class, 'retryRun']);
            $rr->post('/sync',            [RobotController::class, 'sync']);
        });

        // -------------------------
        // Admin (monitoring / ops)
        // -------------------------
        $r->group('/admin', function ($rr) {
            // --- existing ---
            $rr->get('/health', [AdminController::class, 'healthAction']);
            $rr->get('/stats',  [AdminController::class, 'statsAction']);
            $rr->get('/audit',  [AdminController::class, 'auditListAction']);

            // ручные триггеры конвейера
            $rr->post('/cards/:id/parse',   [AdminController::class, 'forceParseAction']);
            $rr->post('/cards/:id/photos',  [AdminController::class, 'forcePhotosAction']);
            $rr->post('/cards/:id/publish', [AdminController::class, 'forcePublishAction']);

            // --- ADDED by Spec: Queues ---
            $rr->get('/queues',                 [AdminController::class, 'listQueuesAction']);
            $rr->get('/queues/:type/jobs',      [AdminController::class, 'listQueueJobsAction']);
            $rr->post('/queues/:type/pause',    [AdminController::class, 'pauseQueueAction']);
            $rr->post('/queues/:type/resume',   [AdminController::class, 'resumeQueueAction']);

            // --- ADDED by Spec: DLQ ---
            $rr->get('/dlq',                    [AdminController::class, 'listDlqAction']);
            $rr->get('/dlq/:id',                [AdminController::class, 'getDlqAction']);
            $rr->post('/dlq/:id/retry',         [AdminController::class, 'retryDlqAction']);
            $rr->post('/dlq/bulk-retry',        [AdminController::class, 'bulkRetryDlqAction']);

            // --- ADDED by Spec: Logs ---
            $rr->get('/logs',                   [AdminController::class, 'listLogsAction']);
        });
    });

    // Можно добавить публичный health без /api, если нужно:
    // $router->get('/healthz', [AdminController::class, 'healthAction']);
};
