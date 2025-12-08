# Auth feature (cabinet/frontend/src/features/auth)

Auth — аутентификация/авторизация для Operator/Admin UI.

## Responsibilities
- Login / Logout
- Refresh token
- Get current user (me)
- Хранение токена в localStorage
- Guard/redirect для защищённых страниц

## API
- `POST /auth/login`    -> { access_token, refresh_token?, user }
- `POST /auth/refresh`  -> { access_token }
- `GET  /auth/me`       -> user
- `POST /auth/logout`   -> ok

## Storage keys
- `autocontent.access_token`
- `autocontent.refresh_token` (опционально)

## Exports
`index.ts` экспортирует schemas/model/api/ui.
