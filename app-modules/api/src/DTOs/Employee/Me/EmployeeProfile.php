<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Me;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;

final readonly class EmployeeProfile
{
    public function __construct(
        public User $user,
        public ?Company $company,
        public ?Department $department,
        public ?Carbon $memberSince,
    ) {}
}
