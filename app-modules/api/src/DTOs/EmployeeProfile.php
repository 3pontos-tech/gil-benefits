<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;

/**
 * O colaborador e o vínculo com a empresa empregadora: empresa, departamento nela e
 * data de entrada.
 */
final readonly class EmployeeProfile
{
    public function __construct(
        public User $user,
        public ?Company $company,
        public ?Department $department,
        public ?Carbon $memberSince,
    ) {}
}
