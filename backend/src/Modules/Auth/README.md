# Auth module

Auth домен отвечает за аутентификацию и сессии Operator/Admin.

## Реализованные endpoints

- `POST /auth/login`
  - вход по email/password, выдаёт access+refresh
- `POST /auth/refresh`
  - обновление access по refresh
- `POST /auth/logout`
  - ревокация refresh-сессии
- `GET /auth/me`
  - возвращает текущего пользователя по Bearer access
- `POST /auth/password-reset/request`
  - запросить сброс пароля (почта/уведомление)
- `POST /auth/password-reset/confirm`
  - подтвердить сброс пароля по токену

## Токены

- `access_token` — JWT HS256, ttl 15 минут, содержит `sub`, `roles`.
- `refresh_token` — случайный токен, хранится хэш в `sessions`.

## Таблицы (дефолтные)

`AuthModel` использует:
- `users`
- `sessions`
- `password_resets`
- `users_roles`
- `audit_logs`

Если схема другая — правим только `AuthModel.php`.

## Слои

- `AuthController.php` — HTTP/REST.
- `AuthService.php` — бизнес-логика токенов/паролей.
- `AuthModel.php` — доступ к БД.
- `AuthSchemas.php` — DTO/валидация форматов.
- `AuthJobs.php` — отправка reset-писем и крон-cleanup.
