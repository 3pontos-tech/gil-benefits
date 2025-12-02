<?php

namespace TresPontosTech\Appointments\Repositories;

use App\Repositories\BaseRepository;
use App\Repositories\Concerns\Cacheable;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentRepository extends BaseRepository
{
    use Cacheable;

    public function __construct(Appointment $model, CacheService $cacheService)
    {
        parent::__construct($model);
        $this->cacheService = $cacheService;
    }

    /**
     * Get appointments with common relationships loaded.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->withCommonRelations()->get();
    }

    /**
     * Get paginated appointments with relationships.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->withCommonRelations()
            ->latest('appointment_at')
            ->paginate($perPage);
    }

    /**
     * Get appointments by status with relationships.
     */
    public function getByStatus(AppointmentStatus $status): Collection
    {
        return $this->model->withCommonRelations()
            ->byStatus($status)
            ->latest('appointment_at')
            ->get();
    }

    /**
     * Get upcoming appointments for a user.
     */
    public function getUpcomingForUser(int $userId): Collection
    {
        return $this->model->withCommonRelations()
            ->where('user_id', $userId)
            ->upcoming()
            ->get();
    }

    /**
     * Get past appointments for a user.
     */
    public function getPastForUser(int $userId): Collection
    {
        return $this->model->withCommonRelations()
            ->where('user_id', $userId)
            ->past()
            ->get();
    }

    /**
     * Get appointments for a consultant within date range.
     */
    public function getForConsultantInDateRange(int $consultantId, Carbon $start, Carbon $end): Collection
    {
        return $this->model->withCommonRelations()
            ->where('consultant_id', $consultantId)
            ->inDateRange($start, $end)
            ->orderBy('appointment_at')
            ->get();
    }

    /**
     * Get appointments for a company.
     */
    public function getForCompany(int $companyId): Collection
    {
        return $this->model->withCommonRelations()
            ->where('company_id', $companyId)
            ->latest('appointment_at')
            ->get();
    }

    /**
     * Get ongoing appointments for a user.
     */
    public function getOngoingForUser(int $userId): Collection
    {
        return $this->model->withCommonRelations()
            ->where('user_id', $userId)
            ->ongoing()
            ->get();
    }

    /**
     * Check if user has ongoing appointments.
     */
    public function hasOngoingAppointments(int $userId): bool
    {
        return $this->model->where('user_id', $userId)
            ->ongoing()
            ->exists();
    }

    /**
     * Get appointments created in the last N days for a user.
     */
    public function getRecentForUser(int $userId, int $days = 30): Collection
    {
        return $this->model->withCommonRelations()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->latest('created_at')
            ->get();
    }

    /**
     * Get appointment statistics for a consultant.
     */
    public function getStatsForConsultant(int $consultantId): array
    {
        return $this->cacheService->remember(
            "appointment_stats:consultant:{$consultantId}",
            function () use ($consultantId) {
                $baseQuery = $this->model->where('consultant_id', $consultantId);

                return [
                    'total' => $baseQuery->count(),
                    'completed' => $baseQuery->byStatus(AppointmentStatus::Completed)->count(),
                    'cancelled' => $baseQuery->byStatus(AppointmentStatus::Cancelled)->count(),
                    'upcoming' => $baseQuery->upcoming()->count(),
                    'this_month' => $baseQuery->where('created_at', '>=', now()->startOfMonth())->count(),
                ];
            },
            1800 // 30 minutes
        );
    }

    /**
     * Get appointment statistics for a company.
     */
    public function getStatsForCompany(int $companyId): array
    {
        return $this->cacheService->remember(
            "appointment_stats:company:{$companyId}",
            function () use ($companyId) {
                $baseQuery = $this->model->where('company_id', $companyId);

                return [
                    'total' => $baseQuery->count(),
                    'completed' => $baseQuery->byStatus(AppointmentStatus::Completed)->count(),
                    'cancelled' => $baseQuery->byStatus(AppointmentStatus::Cancelled)->count(),
                    'upcoming' => $baseQuery->upcoming()->count(),
                    'this_month' => $baseQuery->where('created_at', '>=', now()->startOfMonth())->count(),
                ];
            },
            1800 // 30 minutes
        );
    }

    /**
     * Get appointments that need follow-up (completed but no follow-up action).
     */
    public function getNeedingFollowUp(): Collection
    {
        return $this->model->withCommonRelations()
            ->byStatus(AppointmentStatus::Completed)
            ->where('appointment_at', '<', now()->subDays(7))
            ->get();
    }

    /**
     * Search appointments by user name, consultant name, or external IDs.
     */
    public function search(string $term): Collection
    {
        return $this->model->withCommonRelations()
            ->where(function ($query) use ($term) {
                $query->where('external_opportunity_id', 'like', "%{$term}%")
                    ->orWhere('external_appointment_id', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    })
                    ->orWhereHas('consultant', function ($q) use ($term) {
                        $q->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            })
            ->latest('appointment_at')
            ->get();
    }
}
