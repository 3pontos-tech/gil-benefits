<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Get all records with optional relationships.
     *
     * @param  array<string>  $with
     * @return Collection<int, Model>
     */
    public function all(array $with = []): Collection;

    /**
     * Find a record by ID with optional relationships.
     *
     * @param  array<string>  $with
     */
    public function find(int $id, array $with = []): ?Model;

    /**
     * Find a record by ID or fail.
     *
     * @param  array<string>  $with
     */
    public function findOrFail(int $id, array $with = []): Model;

    /**
     * Create a new record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update a record by ID.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool;

    /**
     * Get paginated records with optional relationships.
     *
     * @param  array<string>  $with
     * @return LengthAwarePaginator<Model>
     */
    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * Get records by specific criteria.
     *
     * @param  array<string>  $with
     * @return Collection<int, Model>
     */
    public function where(string $column, mixed $value, array $with = []): Collection;

    /**
     * Get the first record matching criteria.
     *
     * @param  array<string>  $with
     */
    public function firstWhere(string $column, mixed $value, array $with = []): ?Model;
}
