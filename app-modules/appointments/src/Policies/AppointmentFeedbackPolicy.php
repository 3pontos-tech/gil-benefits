<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Permissions\PermissionsEnum;

class AppointmentFeedbackPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::ViewAny->buildPermissionFor(AppointmentFeedback::class)
        );
    }

    public function view(User $user, AppointmentFeedback $feedback): bool
    {
        return $user->hasPermissionTo(
            PermissionsEnum::View->buildPermissionFor(AppointmentFeedback::class)
        );
    }
}
