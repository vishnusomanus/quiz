<?php

namespace App\Repositories;

use App\Contracts\Repository\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {
    }

    /**
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function find(int $id): ?User
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByUuid(string $uuid): ?User
    {
        return $this->model->newQuery()->where('uuid', $uuid)->first();
    }

    public function findOrFail(int $id): User
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function findOrFailByUuid(string $uuid): User
    {
        return $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function create(array $data): User
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);
        $user->update($data);

        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        $user = $this->findOrFail($id);

        return $user->delete();
    }
}
