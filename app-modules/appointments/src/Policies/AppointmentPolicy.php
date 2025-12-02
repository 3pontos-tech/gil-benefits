<?php

namespace TresPontosTech\Appointments\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    protected function canViewAny(User $user): bool
    {
        // All authenticated users can view appointments (filtered by tenant isolation)
        return true;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var Appointment $model */
        
        // Users can view their own appointments
        if ($model->user_id === $user->id) {
            return true;
        }

        // Owners and managers can view appointments in their companies
        if ($this->isOwnerOrManager($user)) {
            $appointmentUser = $model->user;
            if ($appointmentUser) {
                $userCompanyIds = $user->companies->pluck('id');
                $appointmentUserCompanyIds = $appointmentUser->companies->pluck('id');
                
                return $userCompanyIds->intersect($appointmentUserCompanyIds)->isNotEmpty();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create appointments.
     */
    protected function canCreate(User $user): bool
    {
        // All authenticated users can create appointments
        return true;
    }

    /**
     * Determine whether the user can update the appointment.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Appointment $model */
        
        // Users can update their own appointments if not completed or cancelled
        if ($model->user_id === $user->id) {
            return !in_array($model->status, [
                \TresPontosTech\Appointments\Enums\AppointmentStatus::Completed->value,
                \TresPontosTech\Appointments\Enums\AppointmentStatus::Cancelled->value,
            ]);
        }

        // Owners and managers can update appointments in their companies
        if ($this->isOwnerOrManager($user)) {
            $appointmentUser = $model->user;
            if ($appointmentUser) {
                $userCompanyIds = $user->companies->pluck('id');
                $appointmentUserCompanyIds = $appointmentUser->companies->pluck('id');
                
                return $userCompanyIds->intersect($appointmentUserCompanyIds)->isNotEmpty();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the appointment.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Appointment $model */
        
        // Users can cancel their own appointments if not completed
        if ($model->user_id === $user->id) {
            return $model->status !== \TresPontosTech\Appointments\Enums\AppointmentStatus::Completed->value;
        }

        // Only owners can delete other users' appointments
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the appointment.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Only owners can restore appointments
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the appointment.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Only owners can force delete appointments
        return $this->isOwner($user);
    }
}