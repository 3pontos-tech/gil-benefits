<?php

namespace App\Repositories;

use App\Models\Users\User;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    private CacheService $cacheService;

    public function __construct(User $model, CacheService $cacheService)
    {
        parent::__construct($model);
        $this->cacheService = $cacheService;
    }

    /**
     * Get users with their common relationships loaded.
     *
     * @return Collection<int, User>
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->newQuery()->withCommonRelations()->get();
    }

    /**
     * Get paginated users with relationships.
     *
     * @return LengthAwarePaginator<User>
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->withCommonRelations()->paginate($perPage);
    }

    /**
     * Find user by email with relationships.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()
            ->withCommonRelations()
            ->where('email', $email)
            ->first();
    }

    /**
     * Get partner collaborators with their partner company data.
     *
     * @return Collection<int, User>
     */
    public function getPartnerCollaborators(): Collection
    {
        return $this->cacheService->remember(
            'partner_collaborators',
            fn () => $this->model->newQuery()
                ->withPartnerRelations()
                ->whereHas('companies', function ($query) {
                    $query->whereNotNull('partner_code');
                })
                ->get(),
            1800 // 30 minutes
        );
    }

    /**
     * Get users with appointment data for dashboard.
     *
     * @return Collection<int, User>
     */
    public function getUsersWithAppointmentData(): Collection
    {
        return $this->model->newQuery()
            ->withAppointmentData()
            ->whereHas('appointments')
            ->get();
    }

    /**
     * Get users by company with optimized queries.
     *
     * @return Collection<int, User>
     */
    public function getByCompany(int $companyId): Collection
    {
        return $this->model->newQuery()
            ->withCommonRelations()
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();
    }

    /**
     * Get active users (non-deleted) with relationships.
     *
     * @return Collection<int, User>
     */
    public function getActiveUsers(): Collection
    {
        return $this->model->newQuery()
            ->withCommonRelations()
            ->whereNull('deleted_at')
            ->get();
    }

    /**
     * Search users by name or email with relationships.
     *
     * @return Collection<int, User>
     */
    public function search(string $term): Collection
    {
        return $this->model->newQuery()
            ->withCommonRelations()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->get();
    }

    /**
     * Get users with active subscriptions.
     *
     * @return Collection<int, User>
     */
    public function getUsersWithActiveSubscriptions(): Collection
    {
        return $this->cacheService->remember(
            'users_with_active_subscriptions',
            fn () => $this->model->newQuery()
                ->withCommonRelations()
                ->whereHas('activeSubscription')
                ->get(),
            900 // 15 minutes
        );
    }

    /**
     * Get users who can create appointments (optimized query).
     *
     * @return Collection<int, User>
     */
    public function getUsersWhoCanCreateAppointments(): Collection
    {
        return $this->model->newQuery()
            ->withAppointmentData()
            ->whereHas('activeSubscription.price', function ($query) {
                $query->where('monthly_appointments', '>', 0);
            })
            ->whereDoesntHave('appointments', function ($query) {
                $query->whereNotIn('status', [
                    \TresPontosTech\Appointments\Enums\AppointmentStatus::Completed->value,
                    \TresPontosTech\Appointments\Enums\AppointmentStatus::Cancelled->value,
                ]);
            })
            ->get();
    }
}
