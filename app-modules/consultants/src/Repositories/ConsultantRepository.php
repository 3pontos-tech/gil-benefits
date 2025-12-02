<?php

namespace TresPontosTech\Consultants\Repositories;

use App\Enums\AvailableTagsEnum;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantRepository extends BaseRepository
{
    public function __construct(Consultant $model)
    {
        parent::__construct($model);
    }

    /**
     * Get consultants with common relationships loaded.
     */
    public function getAllWithRelations(): Collection
    {
        return $this->model->withCommonRelations()->get();
    }

    /**
     * Get paginated consultants with relationships.
     */
    public function getPaginatedWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->withCommonRelations()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find consultant by slug with relationships.
     */
    public function findBySlug(string $slug): ?Consultant
    {
        return $this->model->withCommonRelations()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Find consultant by external ID.
     */
    public function findByExternalId(string $externalId): ?Consultant
    {
        return $this->model->withCommonRelations()
            ->where('external_id', $externalId)
            ->first();
    }

    /**
     * Get consultants with appointment statistics.
     */
    public function getWithAppointmentStats(): Collection
    {
        return $this->model->withCommonRelations()
            ->withAppointmentStats()
            ->get();
    }

    /**
     * Get active consultants (non-deleted).
     */
    public function getActiveConsultants(): Collection
    {
        return $this->model->withCommonRelations()
            ->active()
            ->get();
    }

    /**
     * Search consultants by name, email, or description.
     */
    public function search(string $term): Collection
    {
        return $this->model->withCommonRelations()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('short_description', 'like', "%{$term}%")
                    ->orWhere('biography', 'like', "%{$term}%");
            })
            ->get();
    }

    /**
     * Get consultants by expertise tag.
     */
    public function getByExpertise(string $expertise): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('expertises', function ($query) use ($expertise) {
                $query->where('name', $expertise);
            })
            ->get();
    }

    /**
     * Get consultants by specialization tag.
     */
    public function getBySpecialization(string $specialization): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('specializations', function ($query) use ($specialization) {
                $query->where('name', $specialization);
            })
            ->get();
    }

    /**
     * Get consultants by language tag.
     */
    public function getByLanguage(string $language): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('languages', function ($query) use ($language) {
                $query->where('name', $language);
            })
            ->get();
    }

    /**
     * Get consultants with specific tags.
     */
    public function getWithTags(array $tagNames, AvailableTagsEnum $tagType): Collection
    {
        return $this->model->withCommonRelations()
            ->whereHas('tags', function ($query) use ($tagNames, $tagType) {
                $query->whereIn('name', $tagNames)
                    ->where('type', $tagType->value);
            })
            ->get();
    }

    /**
     * Get top-rated consultants (by appointment completion rate).
     */
    public function getTopRated(int $limit = 10): Collection
    {
        return $this->model->withCommonRelations()
            ->withAppointmentStats()
            ->having('completed_appointments_count', '>', 0)
            ->orderByRaw('completed_appointments_count / appointments_count DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get consultants with most appointments.
     */
    public function getMostActive(int $limit = 10): Collection
    {
        return $this->model->withCommonRelations()
            ->withAppointmentStats()
            ->orderBy('appointments_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recently added consultants.
     */
    public function getRecentlyAdded(int $days = 30): Collection
    {
        return $this->model->withCommonRelations()
            ->where('created_at', '>=', now()->subDays($days))
            ->latest('created_at')
            ->get();
    }

    /**
     * Get consultant statistics.
     */
    public function getStats(int $consultantId): array
    {
        $consultant = $this->findOrFail($consultantId, [
            'appointments',
            'languages',
            'expertises',
            'specializations',
            'degrees',
        ]);

        $totalAppointments = $consultant->appointments->count();
        $completedAppointments = $consultant->appointments()
            ->where('status', \TresPontosTech\Appointments\Enums\AppointmentStatus::Completed)
            ->count();

        return [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'completion_rate' => $totalAppointments > 0 ? ($completedAppointments / $totalAppointments) * 100 : 0,
            'languages_count' => $consultant->languages->count(),
            'expertises_count' => $consultant->expertises->count(),
            'specializations_count' => $consultant->specializations->count(),
            'degrees_count' => $consultant->degrees->count(),
            'profile_completeness' => $this->calculateProfileCompleteness($consultant),
        ];
    }

    /**
     * Calculate profile completeness percentage.
     */
    private function calculateProfileCompleteness(Consultant $consultant): float
    {
        $fields = [
            'name' => ! empty($consultant->name),
            'email' => ! empty($consultant->email),
            'phone' => ! empty($consultant->phone),
            'short_description' => ! empty($consultant->short_description),
            'biography' => ! empty($consultant->biography),
            'languages' => $consultant->languages->count() > 0,
            'expertises' => $consultant->expertises->count() > 0,
            'specializations' => $consultant->specializations->count() > 0,
        ];

        $completedFields = array_filter($fields);

        return (count($completedFields) / count($fields)) * 100;
    }

    /**
     * Get consultants available for appointments (with capacity).
     */
    public function getAvailableForAppointments(): Collection
    {
        return $this->model->withCommonRelations()
            ->active()
            ->whereDoesntHave('appointments', function ($query) {
                $query->where('appointment_at', '>', now())
                    ->where('status', '!=', \TresPontosTech\Appointments\Enums\AppointmentStatus::Cancelled);
            })
            ->get();
    }
}
