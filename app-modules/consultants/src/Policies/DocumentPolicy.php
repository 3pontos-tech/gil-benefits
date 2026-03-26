<?php

namespace TresPontosTech\Consultants\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Consultants\Document;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {

        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Document $document): bool
    {
        return true;
    }

    public function delete(User $user, Document $document): bool
    {
        return true;
    }

    public function restore(User $user, Document $document): bool {}

    public function forceDelete(User $user, Document $document): bool {}
}
