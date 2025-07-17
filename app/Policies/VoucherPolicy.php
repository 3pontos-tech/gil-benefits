<?php

namespace App\Policies;

use App\Models\Users\User;
use App\Models\Voucher;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Voucher $voucher): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Voucher $voucher): bool
    {
        return true;
    }

    public function delete(User $user, Voucher $voucher): bool
    {
        return true;
    }

    public function restore(User $user, Voucher $voucher): bool
    {
        return true;
    }

    public function forceDelete(User $user, Voucher $voucher): bool
    {
        return true;
    }
}
