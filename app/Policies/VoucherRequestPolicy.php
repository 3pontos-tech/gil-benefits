<?php

namespace App\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use TresPontosTech\Vouchers\Models\VoucherRequest;

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
