<?php

namespace App\Observers;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Model;

class CacheInvalidationObserver
{
    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateRelatedCache($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateRelatedCache($model);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateRelatedCache($model);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->invalidateRelatedCache($model);
    }

    /**
     * Invalidate cache related to the model.
     */
    private function invalidateRelatedCache(Model $model): void
    {
        $modelType = strtolower(class_basename($model));
        $modelId = $model->getKey();

        // Invalidate general cache for this model type
        $this->cacheService->invalidateRelatedCache($modelType, $modelId);

        // Model-specific cache invalidation
        switch ($modelType) {
            case 'user':
                $this->invalidateUserCache($model);
                break;
            case 'company':
                $this->invalidateCompanyCache($model);
                break;
            case 'appointment':
                $this->invalidateAppointmentCache($model);
                break;
            case 'consultant':
                $this->invalidateConsultantCache($model);
                break;
        }
    }

    /**
     * Invalidate user-specific cache.
     */
    private function invalidateUserCache(Model $user): void
    {
        $userId = $user->getKey();

        // Invalidate user data cache
        $this->cacheService->forgetUserData($userId);

        // Invalidate related caches
        $this->cacheService->forget('users_with_active_subscriptions');
        $this->cacheService->forget('partner_collaborators');

        // Invalidate dashboard cache for this user
        $panels = ['admin', 'user', 'company', 'consultant'];
        foreach ($panels as $panel) {
            $this->cacheService->forget("dashboard:{$panel}:{$userId}");
        }
    }

    /**
     * Invalidate company-specific cache.
     */
    private function invalidateCompanyCache(Model $company): void
    {
        $companyId = $company->getKey();

        // Invalidate company data cache
        $this->cacheService->forgetCompanyData($companyId);

        // Invalidate related caches
        $this->cacheService->forget('partner_collaborators');

        // If this is a partner company, invalidate partner-related caches
        if (! empty($company->partner_code)) {
            $this->cacheService->forgetByPattern('partner:*');
        }
    }

    /**
     * Invalidate appointment-specific cache.
     */
    private function invalidateAppointmentCache(Model $appointment): void
    {
        // Invalidate statistics for related entities
        if (isset($appointment->user_id)) {
            $this->cacheService->forget("appointment_stats:user:{$appointment->user_id}");
        }

        if (isset($appointment->company_id)) {
            $this->cacheService->forget("appointment_stats:company:{$appointment->company_id}");
        }

        if (isset($appointment->consultant_id)) {
            $this->cacheService->forget("appointment_stats:consultant:{$appointment->consultant_id}");
        }

        // Invalidate user's monthly appointments cache
        if (isset($appointment->user_id)) {
            $this->cacheService->forget("user:{$appointment->user_id}:monthly_appointments_left");
        }
    }

    /**
     * Invalidate consultant-specific cache.
     */
    private function invalidateConsultantCache(Model $consultant): void
    {
        $consultantId = $consultant->getKey();

        // Invalidate consultant statistics
        $this->cacheService->forget("appointment_stats:consultant:{$consultantId}");

        // Invalidate consultant-related queries
        $this->cacheService->forgetByPattern('consultant:*');
    }
}
