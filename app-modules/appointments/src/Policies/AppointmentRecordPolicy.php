<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

class AppointmentRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::ViewAny->buildPermissionFor(AppointmentRecord::class)
        );
    }

    public function view(User $user, AppointmentRecord $record): bool
    {
        if (! $user->hasPermissionTo(
            PermissionsEnum::View->buildPermissionFor(AppointmentRecord::class)
        )) {
            return false;
        }

        if ($user->hasAnyRole([
            Roles::SuperAdmin->value,
            Roles::Admin->value,
            Roles::Consultant->value,
        ])) {
            return true;
        }

        return $record->isPublished()
            && $record->appointment->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::Create->buildPermissionFor(AppointmentRecord::class)
        );
    }

    public function update(User $user, AppointmentRecord $record): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::Update->buildPermissionFor(AppointmentRecord::class)
        );
    }

    public function delete(User $user, AppointmentRecord $record): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::Delete->buildPermissionFor(AppointmentRecord::class)
        );
    }

    public function restore(User $user, AppointmentRecord $record): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::Restore->buildPermissionFor(AppointmentRecord::class)
        );
    }

    public function forceDelete(User $user, AppointmentRecord $record): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::ForceDelete->buildPermissionFor(AppointmentRecord::class)
        );
    }
}
