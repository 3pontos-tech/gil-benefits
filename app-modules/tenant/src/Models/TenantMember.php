<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Permissions\Roles;

/**
 * @property string $user_id
 * @property string $company_id
 * @property string|null $department_id
 * @property Roles|null $role
 * @property bool $active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TenantMember extends Pivot
{
    public $timestamps = true;

    protected $fillable = [
        'role',
        'active',
        'department_id',
    ];

    protected $casts = [
        'role' => Roles::class,
        'active' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
