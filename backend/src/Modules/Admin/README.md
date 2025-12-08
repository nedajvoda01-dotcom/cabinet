# Admin module

Доменный модуль для административных операций.

## Layering

- `AdminController.php` — HTTP/REST входные точки.
- `AdminService.php` — бизнес-логика.
- `AdminModel.php` — доступ к данным.
- `AdminSchemas.php` — DTO/валидация форматов.
- `AdminJobs.php` — фоновые задачи.

## Current endpoints (draft)

> Эти эндпоинты-заглушки нужны как шаблон.  
> После первой реальной фичи список обновим.

- `GET /admin/{id}` → `AdminController::getAdminAction`
- `GET /admin` → `AdminController::listAdminsAction`
- `POST /admin/example` → `AdminController::exampleAction`

## Notes

- Бизнес-валидация делается в `AdminService`.
- Любые новые форматы запросов/ответов добавляются через `AdminSchemas`.
- Если понадобятся статусы/StateMachine или внешние контракты — добавим отдельно по Spec.
