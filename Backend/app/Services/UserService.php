<?php

namespace App\Services;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function getAll(): Collection
    {
        return $this->userRepository->all();
    }

    /**
     * Get a user by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): User
    {
        return $this->userRepository->findOrFail($id);
    }

    /**
     * Get a user by UUID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getByUuid(string $uuid): User
    {
        return $this->userRepository->findOrFailByUuid($uuid);
    }

    /**
     * Get a user by email.
     */
    public function getByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Paginate users.
     *
     * @return LengthAwarePaginator<User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * Create a new user (business logic can be added here, e.g. validation, events).
     */
    public function create(array $data): User
    {
        return $this->userRepository->create($data);
    }

    /**
     * Update a user by ID.
     */
    public function update(int $id, array $data): User
    {
        return $this->userRepository->update($id, $data);
    }

    /**
     * Delete a user by ID.
     */
    public function delete(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Build the public user payload with UUID as id.
     *
     * @return array<string, mixed>
     */
    public function toPublicPayload(User $user): array
    {
        $publicUuid      = $user->getOrCreateUuid();
        $payload         = $user->toArray();
        $payload['id']   = $publicUuid;
        $payload['uuid'] = $publicUuid;

        return $payload;
    }
}
