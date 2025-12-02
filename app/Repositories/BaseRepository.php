<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    /**
     * @param  array<string>  $with
     * @return Collection<int, Model>
     */
    public function all(array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function find(int $id, array $with = []): ?Model
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->find($id);
    }

    /**
     * @param  array<string>  $with
     */
    public function findOrFail(int $id, array $with = []): Model
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh() ?? $model;
    }

    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete() ?? false;
    }

    /**
     * @param  array<string>  $with
     * @return LengthAwarePaginator<Model>
     */
    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string>  $with
     * @return Collection<int, Model>
     */
    public function where(string $column, mixed $value, array $with = []): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->where($column, $value)->get();
    }

    /**
     * @param  array<string>  $with
     */
    public function firstWhere(string $column, mixed $value, array $with = []): ?Model
    {
        $query = $this->model->newQuery();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query->where($column, $value)->first();
    }

    /**
     * Get the model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the model instance.
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }
}
