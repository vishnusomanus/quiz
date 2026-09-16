<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected UserService $userService,
        protected AuditLogService $auditLogService,
    ) {
    }

    /**
     * Return the authenticated user (current API user).
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->sendError('Unauthenticated', null, 401);
            }

            $resource = $this->userService->toPublicPayload($user);

            $this->auditLogService->log([
                'action'     => config('constants.USER_VIEW'),
                'entity'     => 'user',
                'entity_id'  => $user->id,
                'change_log' => ['viewed_self' => true],
            ]);

            return $this->sendResponse($resource, 'Success', 200, true);
        } catch (Throwable $e) {
            Log::channel('user_errors')->info('User me error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
            ]);

            $errorPayload = [
                'detail' => $e->getMessage(),
            ];

            if (app()->environment('local')) {
                $errorPayload['trace'] = $e->getTraceAsString();
                $errorPayload['line']  = $e->getLine();
            }

            return $this->sendError('Failed to get user', [
                ...$errorPayload,
            ], 500);
        }
    }

    /**
     * List users (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $users   = $this->userService
                ->paginate($perPage)
                ->through(function (User $user): array {
                    return $this->userService->toPublicPayload($user);
                });

            $this->auditLogService->log([
                'action'     => config('constants.USER_LIST'),
                'entity'     => 'user',
                'entity_id'  => null,
                'change_log' => ['per_page' => $perPage, 'total' => $users->total()],
            ]);

            return $this->sendPaginatedResponse($users, 'Success', 200, true);
        } catch (Throwable $e) {
            Log::channel('user_errors')->info('User list error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
            ]);

            $errorPayload = [
                'detail' => $e->getMessage(),
            ];

            if (app()->environment('local')) {
                $errorPayload['trace'] = $e->getTraceAsString();
                $errorPayload['line']  = $e->getLine();
            }

            return $this->sendError('Failed to list users', [
                ...$errorPayload,
            ], 500);
        }
    }

    /**
     * Show a user by UUID.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        try {
            $user = $this->userService->getByUuid($uuid);

            $this->auditLogService->log([
                'action'     => config('constants.USER_VIEW'),
                'entity'     => 'user',
                'entity_id'  => $user->id,
                'change_log' => ['viewed_by' => $request->user()?->id],
            ]);

            return $this->sendResponse($this->userService->toPublicPayload($user), 'Success', 200, true);
        } catch (Throwable $e) {
            Log::channel('user_errors')->info('User show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
            ]);

            $errorPayload = [
                'detail' => $e->getMessage(),
            ];

            if (app()->environment('local')) {
                $errorPayload['trace'] = $e->getTraceAsString();
                $errorPayload['line']  = $e->getLine();
            }

            return $this->sendError('Failed to get user', [
                ...$errorPayload,
            ], 500);
        }
    }
}
