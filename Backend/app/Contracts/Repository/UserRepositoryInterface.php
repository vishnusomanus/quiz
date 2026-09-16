<?php

namespace App\Contracts\Repository;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Find a user by ID.
     */
    public function find(int $id): ?User;

    /**
     * Find a user by UUID.
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * Find a user by ID or throw.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User;

    /**
     * Find a user by UUID or throw.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailByUuid(string $uuid): User;

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Paginate users.
     *
     * @return LengthAwarePaginator<User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update a user by ID.
     */
    public function update(int $id, array $data): User;

    /**
     * Delete a user by ID.
     */
    public function delete(int $id): bool;
}
