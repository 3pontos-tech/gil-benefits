<?php

namespace App\Policies;

use App\Models\Users\User;
use App\Models\VoucherRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VoucherRequest $voucherRequest): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VoucherRequest $voucherRequest): bool
    {
        return true;
    }

    public function delete(User $user, VoucherRequest $voucherRequest): bool
    {
        return true;
    }

    public function restore(User $user, VoucherRequest $voucherRequest): bool
    {
        return true;
    }

    public function forceDelete(User $user, VoucherRequest $voucherRequest): bool
    {
        return true;
    }
}
