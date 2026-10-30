<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Exceptions\EmployedElsewhere;

/**
 * @property string $id
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
    use HasUuids;

    protected $table = 'company_employees';

    public $timestamps = true;

    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            $employer = $member->otherActiveEmployer();

            throw_if($employer instanceof Company, EmployedElsewhere::class, $employer);
        });
    }

    /**
     * The company where this person is already an active employee, when this
     * row would become a second one. Owner and manager rows and the default
     * bucket are not employers.
     */
    public function otherActiveEmployer(): ?Company
    {
        if (! $this->countsAsEmployer()) {
            return null;
        }

        return Company::query()
            ->withoutDefault()
            ->whereKeyNot($this->company_id)
            ->whereIn('id', self::query()
                ->select('company_id')
                ->where('user_id', $this->user_id)
                ->where('role', Roles::Employee->value)
                ->where('active', true)
                ->whereNull('deleted_at'))
            ->first();
    }

    private function countsAsEmployer(): bool
    {
        return $this->role === Roles::Employee
            && ($this->active ?? true)
            && $this->deleted_at === null
            && $this->company_id !== Company::defaultId();
    }

    /**
     * A membership created without an explicit role is a plain employee: the
     * database refuses a null role, and employee is the weakest one.
     */
    protected $attributes = [
        'role' => Roles::Employee->value,
    ];

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
