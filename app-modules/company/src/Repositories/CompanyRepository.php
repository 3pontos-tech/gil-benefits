<?php

namespace TresPontosTech\Company\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use TresPontosTech\Company\Models\Company;

class CompanyRepository extends BaseRepository
{
    public function __construct(Company $model)
    {
        parent::__construct($model);
    }

    /**
     * Get companies with common relationships loaded.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->withCommonRelations()->get();
    }

    /**
     * Get paginated companies with relationships.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->withCommonRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find company by slug with relationships.
     */
    public function findBySlug(string $slug): ?Company
    {
        return $this->model->withCommonRelations()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Find company by partner code with relationships.
     */
    public function findByPartnerCode(string $code): ?Company
    {
        return $this->model->withCommonRelations()
            ->where('partner_code', $code)
            ->first();
    }

    /**
     * Get all partner companies.
     */
    public function getPartnerCompanies(): Collection
    {
        return $this->model->withCommonRelations()
            ->partners()
            ->get();
    }

    /**
     * Get companies with active subscriptions.
     */
    public function getWithActiveSubscriptions(): Collection
    {
        return $this->model->withCommonRelations()
            ->withActiveSubscriptions()
            ->get();
    }

    /**
     * Get companies owned by a specific user.
     */
    public function getOwnedByUser(int $userId): Collection
    {
        return $this->model->withCommonRelations()
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get companies where user is an employee.
     */
    public function getWhereUserIsEmployee(int $userId): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('employees', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
    }

    /**
     * Get active companies (non-deleted).
     */
    public function getActiveCompanies(): Collection
    {
        return $this->model->withCommonRelations()
            ->active()
            ->get();
    }

    /**
     * Search companies by name or slug.
     */
    public function search(string $term): Collection
    {
        return $this->model->withCommonRelations()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('partner_code', 'like', "%{$term}%");
            })
            ->get();
    }

    /**
     * Get companies with employee count.
     */
    public function getWithEmployeeCount(): Collection
    {
        return $this->model->withCommonRelations()
            ->withCount('employees')
            ->get();
    }

    /**
     * Get companies that need attention (no active subscription, etc.).
     */
    public function getNeedingAttention(): Collection
    {
        return $this->model->withCommonRelations()
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->where('stripe_status', 'active');
            })
            ->orWhere('created_at', '<', now()->subDays(30))
            ->whereDoesntHave('subscriptions')
            ->get();
    }

    /**
     * Get company statistics.
     */
    public function getStats(int $companyId): array
    {
        $company = $this->findOrFail($companyId, ['employees', 'subscriptions']);

        return [
            'employees_count' => $company->employees->count(),
            'active_subscriptions' => $company->subscriptions()
                ->where('stripe_status', 'active')
                ->count(),
            'total_subscriptions' => $company->subscriptions->count(),
            'is_partner' => ! is_null($company->partner_code),
            'created_days_ago' => $company->created_at->diffInDays(now()),
        ];
    }

    /**
     * Get companies created in the last N days.
     */
    public function getRecentlyCreated(int $days = 30): Collection
    {
        return $this->model->withCommonRelations()
            ->where('created_at', '>=', now()->subDays($days))
            ->latest('created_at')
            ->get();
    }

    /**
     * Get companies by subscription status.
     */
    public function getBySubscriptionStatus(string $status): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('subscriptions', function ($query) use ($status) {
                $query->where('stripe_status', $status);
            })
            ->get();
    }
}
