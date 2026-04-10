<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Permissions\PermissionsEnum;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Document::class));
    }

    public function view(User $user, Document $document): bool
    {

        return $user->hasPermissionTo(PermissionsEnum::View->buildPermissionFor(Document::class));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Create->buildPermissionFor(Document::class));
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Update->buildPermissionFor(Document::class));
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Delete->buildPermissionFor(Document::class));
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::Restore->buildPermissionFor(Document::class));
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(Document::class));
    }
}
