<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class RoleMiddleware
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $routeUserId = $request->route('id');

        try {
            $roleEnums = array_map(fn (string $role) => UserRole::from($role), $roles);
        } catch (ValueError $exception) {
            $allowedRoles = implode(', ', array_map(
                fn (UserRole $role): string => $role->value,
                UserRole::cases(),
            ));

            return response()->json([
                'message' => 'Invalid role provided. Allowed roles: '.$allowedRoles.'.',
            ], 422);
        }

        if (\in_array(UserRole::CurrentUser, $roleEnums)) {
            if ($user && $user->id == $routeUserId) {
                return $next($request);
            }
        }

        if (! $user || ! $this->roleService->hasAnyRole($user, $roleEnums)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
