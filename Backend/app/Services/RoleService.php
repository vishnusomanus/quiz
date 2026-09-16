<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class RoleService
{
    public function hasAnyRole(User $user, array $roles): bool
    {
        $roles = array_filter($roles, fn ($role) => $role !== UserRole::CurrentUser);

        return \in_array($user->role, $roles, true);
    }
}
