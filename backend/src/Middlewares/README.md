# Middlewares

Инфраструктурный слой HTTP-гейтинга.

## Responsibilities
- AuthMiddleware: проверка access token, загрузка пользователя, блокировки.
- RoleMiddleware: проверка ролей (admin/operator).
- AdminMiddleware: сахар для RoleMiddleware('admin').
- CorsMiddleware: CORS для frontend/cabinet.

## Rules
- Middlewares не содержат доменной логики, только гейтинг.
- Не обращаются к Adapters.
- Могут использовать AuthService / UsersService / Repositories.
