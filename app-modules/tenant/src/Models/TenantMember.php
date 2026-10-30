<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            if ($member->exists && ! $member->isDirty(['role', 'active', 'deleted_at', 'company_id', 'user_id'])) {
                return;
            }

            $employer = $member->otherActiveEmployer();

            throw_if($employer instanceof Company, EmployedElsewhere::class, $employer);
        });
    }

    /**
     * The company where this person is already an active employee, when this
     * row would become a second one. Owner and manager rows and the default
     * bucket are not employers. A soft-deleted company still counts, as it
     * does for the database index: closing a company soft-deletes its
     * memberships, and only that releases its people.
     */
    public function otherActiveEmployer(): ?Company
    {
        if ($this->role !== Roles::Employee || ! ($this->active ?? true) || $this->deleted_at !== null) {
            return null;
        }

        return self::activeEmployerElsewhere($this->user_id, $this->company_id);
    }

    /**
     * Where the person is an active employee other than at the given company,
     * or null when there is none or when the given company is the default
     * bucket. One query, so bulk attaches stay cheap.
     */
    public static function activeEmployerElsewhere(string $userId, string $companyId): ?Company
    {
        return Company::withTrashed()
            ->withoutDefault()
            ->whereKeyNot($companyId)
            ->whereIn('id', self::query()
                ->select('company_id')
                ->where('user_id', $userId)
                ->where('role', Roles::Employee->value)
                ->where('active', true)
                ->whereNull('deleted_at'))
            ->whereNotExists(fn ($query) => $query
                ->from('companies', 'given')
                ->whereColumn('given.id', DB::raw("'" . $companyId . "'"))
                ->where('given.slug', Company::DEFAULT_SLUG))
            ->first();
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
