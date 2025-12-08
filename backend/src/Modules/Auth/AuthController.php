<?php
declare(strict_types=1);

namespace Backend\Modules\Auth;

use Backend\Http\Request;
use Backend\Http\Response;
use InvalidArgumentException;
use Throwable;

/**
 * AuthController
 *
 * Endpoints:
 *  POST /auth/login
 *  POST /auth/refresh
 *  POST /auth/logout
 *  GET  /auth/me
 *  POST /auth/password-reset/request
 *  POST /auth/password-reset/confirm
 */
final class AuthController
{
    public function __construct(private AuthService $service) {}

    public function loginAction(Request $req): Response
    {
        try {
            $dto = AuthSchemas::toLoginDto($req->json());
            $ua = (string)($req->header('User-Agent') ?? '');
            $ip = (string)($req->ip() ?? '');
            $result = $this->service->login($dto, $ua, $ip);

            return Response::json(AuthSchemas::ok($result), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 401);
        }
    }

    public function refreshAction(Request $req): Response
    {
        try {
            $dto = AuthSchemas::toRefreshDto($req->json());
            $result = $this->service->refresh($dto);

            return Response::json(AuthSchemas::ok($result), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 401);
        }
    }

    public function logoutAction(Request $req): Response
    {
        try {
            $dto = AuthSchemas::toLogoutDto($req->json());
            $this->service->logout($dto);

            return Response::json(AuthSchemas::ok(['revoked' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail('Internal error'), 500);
        }
    }

    public function meAction(Request $req): Response
    {
        try {
            $authHeader = (string)($req->header('Authorization') ?? '');
            $jwt = trim(str_ireplace('Bearer', '', $authHeader));

            if ($jwt === '') {
                return Response::json(AuthSchemas::fail('Missing token'), 401);
            }

            $payload = $this->service->verifyAccessToken($jwt);
            $user = $this->service->me((int)$payload['sub']);

            return Response::json(AuthSchemas::ok($user), 200);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 401);
        }
    }

    public function requestPasswordResetAction(Request $req): Response
    {
        try {
            $dto = AuthSchemas::toRequestResetDto($req->json());
            $this->service->requestPasswordReset($dto);

            // всегда ok, чтобы не раскрывать наличие email
            return Response::json(AuthSchemas::ok(['sent' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail('Internal error'), 500);
        }
    }

    public function confirmPasswordResetAction(Request $req): Response
    {
        try {
            $dto = AuthSchemas::toConfirmResetDto($req->json());
            $this->service->confirmPasswordReset($dto);

            return Response::json(AuthSchemas::ok(['updated' => true]), 200);
        } catch (InvalidArgumentException $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 400);
        } catch (Throwable $e) {
            return Response::json(AuthSchemas::fail($e->getMessage()), 401);
        }
    }
}
