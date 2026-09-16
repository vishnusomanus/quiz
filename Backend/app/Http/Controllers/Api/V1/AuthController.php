<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuditLogService;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService,
        protected AuditLogService $auditLogService,
    ) {}

    /**
     * Login using email and password. Returns a Passport token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            $this->auditLogService->log([
                'action' => config('constants.LOGIN'),
                'entity' => 'user',
                'entity_id' => $result['activityLog']['user_id'] ?? null,
                'change_log' => $result['activityLog'],
            ]);

            return $this->sendResponse(
                $result['data'],
                $result['message'],
                $result['code'],
                $result['status'],
            );
        } catch (Throwable $e) {
            Log::channel('auth_errors')->info('Login error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);

            $code = $e instanceof ValidationException ? 422 : 500;

            $errorPayload = [
                'detail' => $e->getMessage(),
            ];

            if (app()->environment('local')) {
                $errorPayload['trace'] = $e->getTraceAsString();
                $errorPayload['line'] = $e->getLine();
            }

            return $this->sendError('Login failed', [
                ...$errorPayload,
            ], $code);
        }
    }

    /**
     * Logout: revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->authService->logout($user);

            $this->auditLogService->log([
                'action' => config('constants.LOGOUT'),
                'entity' => 'user',
                'entity_id' => $result['activityLog']['user_id'] ?? null,
                'change_log' => $result['activityLog'],
            ]);

            return $this->sendResponse(
                $result['data'],
                $result['message'],
                $result['code'],
                $result['status'],
            );
        } catch (Throwable $e) {
            Log::channel('auth_errors')->info('Logout error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);

            $errorPayload = [
                'detail' => $e->getMessage(),
            ];

            if (app()->environment('local')) {
                $errorPayload['trace'] = $e->getTraceAsString();
                $errorPayload['line'] = $e->getLine();
            }

            return $this->sendError('Logout failed', [
                ...$errorPayload,
            ], 500);
        }
    }
}
