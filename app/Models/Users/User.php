<?php

namespace App\Models\Users;

use App\Filament\FilamentPanel;
use App\Observers\UserObserver;
use App\Policies\Users\UserPolicy;
use Database\Factories\Users\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Billable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Permissions\Role;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Models\Traits\HasTenant;
use TresPontosTech\User\Models\UserAnamnese;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $crm_id
 * @property string|null $external_id
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $remember_token
 * @property-read int $monthly_appointments_left
 */
#[UseFactory(UserFactory::class)]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasMedia, HasTenants
{
    use Billable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasTenant;
    use HasUuids;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'crm_id',
        'external_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return FilamentPanel::canAccessPanel($panel, $this);
    }

    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->isAdmin();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // The management (company) panel is for owners/managers of that specific
        // company. Being merely an employee there is not enough.
        if (Filament::getCurrentPanel()?->getId() === FilamentPanel::Company->value) {
            if (! $tenant instanceof Company) {
                return false;
            }
            if ($this->isCompanyOwner($tenant)) {
                return true;
            }

            return $this->isCompanyManager($tenant);
        }

        // Other panels (app): any active membership of that company.
        if ($this->ownedCompanies()->whereKey($tenant)->exists()) {
            return true;
        }

        return (bool) $this->companies()->whereKey($tenant)->wherePivot('active', true)->exists();
    }

    /**
     * @return Collection<int, Company>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isAdmin()) {
            return Company::query()->orderBy('name')->get();
        }

        // Management panel lists only companies the user owns or manages.
        if ($panel->getId() === FilamentPanel::Company->value) {
            return $this->ownedCompanies()
                ->orWhereIn('id', $this->companies()
                    ->wherePivot('active', true)
                    ->wherePivot('role', Roles::CompanyManager->value)
                    ->select('companies.id'))
                ->orderBy('name')
                ->get();
        }

        // App panel lists every company the user is an active member of.
        return $this->ownedCompanies()
            ->orWhereIn('id', $this->companies()->wherePivot('active', true)->select('companies.id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return BelongsToMany<Company, $this, TenantMember>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_employees', 'user_id', 'company_id')
            ->withTimestamps()
            ->withPivot(['role'])
            ->using(TenantMember::class);
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    /**
     * @return HasOne<Detail, $this>
     */
    public function detail(): HasOne
    {
        return $this->hasOne(Detail::class);
    }

    /** @return HasOne<UserAnamnese, $this> */
    public function anamnese(): HasOne
    {
        return $this->hasOne(UserAnamnese::class);
    }

    /**
     * @return HasOne<Consultant, $this>
     */
    public function consultant(): HasOne
    {
        return $this->hasOne(Consultant::class);
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * @return HasMany<DocumentShare, $this>
     */
    public function sharedDocuments(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'employee_id');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function whereNotSharedWith(Builder $query, string $documentId): Builder
    {
        return $query->whereDoesntHave('sharedDocuments', function (Builder $subquery) use ($documentId): void {
            $subquery->where('document_id', $documentId);
        });
    }

    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([Roles::SuperAdmin, Roles::Admin]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Roles::SuperAdmin);
    }

    /**
     * Effective role of the user WITHIN a company (the pivot is the source of truth).
     * Without an argument, uses the current Filament tenant.
     */
    public function tenantRole(?Company $company = null): ?Roles
    {
        $company ??= Filament::getTenant();

        if (! $company instanceof Company) {
            return null;
        }

        // The real company owner takes precedence over the pivot.
        if ($company->user_id === $this->getKey()) {
            return Roles::CompanyOwner;
        }

        return $this->companies()->whereKey($company)->first()?->pivot?->role;
    }

    public function isCompanyOwner(?Company $company = null): bool
    {
        return $this->tenantRole($company) === Roles::CompanyOwner;
    }

    public function isCompanyManager(?Company $company = null): bool
    {
        return $this->tenantRole($company) === Roles::CompanyManager;
    }

    public function isEmployee(?Company $company = null): bool
    {
        return $this->tenantRole($company) === Roles::Employee;
    }

    /**
     * Owns at least one company (is the owner in `companies.user_id`).
     */
    public function ownsAnyCompany(): bool
    {
        return $this->ownedCompanies()->exists();
    }

    /**
     * Owns or manages at least one company (manager role in the pivot).
     */
    public function managesAnyCompany(): bool
    {
        if ($this->ownsAnyCompany()) {
            return true;
        }

        return $this->companies()->wherePivot('role', Roles::CompanyManager->value)->exists();
    }

    /**
     * Checks a PER-TENANT permission.
     *
     * Truly global roles (super_admin, admin, consultant) grant permission anywhere.
     * Company roles (owner, manager, employee) only apply within their company,
     * read from the pivot — never from a global role (which may be stale).
     */
    public function hasTenantPermission(string $permission, ?Company $company = null): bool
    {
        // Only truly global roles grant permission outside a company.
        // "user"/"employee" are excluded: they are baseline/per-tenant and their
        // permissions come from the company pivot — otherwise the global "user"
        // role (which everyone has) would reopen the leak (e.g. view_any_users).
        $globalRoles = [
            Roles::SuperAdmin->value,
            Roles::Admin->value,
            Roles::Consultant->value,
        ];

        $grantedByGlobalRole = $this->roles()
            ->whereIn('name', $globalRoles)
            ->whereHas('permissions', fn (Builder $query): Builder => $query->where('name', $permission))
            ->exists();

        if ($grantedByGlobalRole) {
            return true;
        }

        $tenantRole = $this->tenantRole($company);

        if (! $tenantRole instanceof Roles) {
            return false;
        }

        return Role::query()
            ->where('name', $tenantRole->value)
            ->whereHas('permissions', fn (Builder $query): Builder => $query->where('name', $permission))
            ->exists();
    }

    public function forgetMonthlyAppointmentsLeftCache(): void
    {
        if ($this->getKey() === null) {
            return;
        }

        Cache::forget($this->getMonthlyAppointmentsLeftCacheKey());
    }

    protected function getMonthlyAppointmentsLeftCacheKey(): string
    {
        /** @var string $key */
        $key = $this->getKey();

        return sprintf('user:%s:monthly_appointments_left', $key);
    }

    /**
     * Determine if the user is eligible to create a new appointment.
     *
     * Rules:
     * - Must have monthly appointments left.
     * - Must not have any ongoing appointment (i.e., previous one must be completed or cancelled).
     */
    public function canCreateAppointment(): bool
    {
        return ($this->monthly_appointments_left > 0 || $this->hasAvailableCredit())
            && ! $this->hasOngoingAppointment();
    }

    public function hasAvailableCredit(): bool
    {
        return UserCredit::query()
            ->where('holder_id', $this->getKey())
            ->where('status', UserCreditStatusEnum::Available)
            ->exists();
    }

    /** @return HasMany<UserCredit, $this> */
    public function credits(): HasMany
    {
        return $this->hasMany(UserCredit::class, 'holder_id');
    }

    /**
     * Extra-credit grants the admin donated directly to this user (personal gifts).
     *
     * @return HasMany<CreditGrant, $this>
     */
    public function receivedCreditGrants(): HasMany
    {
        return $this->hasMany(CreditGrant::class, 'target_user_id')->latest();
    }

    /**
     * Determine if the user currently has any appointment that is not completed or cancelled.
     */
    public function hasOngoingAppointment(): bool
    {
        return $this->appointments()
            ->whereNotIn('status', [
                AppointmentStatus::Completed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::CancelledLate->value,
            ])
            ->exists();
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Computed, cached monthly appointments left in the last 30 days window.
     *
     * @return Attribute<int, never>
     */
    protected function monthlyAppointmentsLeft(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->getKey() === null) {
                    return 0;
                }

                $cacheKey = $this->getMonthlyAppointmentsLeftCacheKey();

                /** @var int $result */
                $result = Cache::remember($cacheKey, now()->addMinute(), function (): int {
                    $monthlyLimit = $this->resolveMonthlyAppointmentLimit();

                    if ($monthlyLimit <= 0) {
                        return 0;
                    }

                    $used = (int) $this->appointments()
                        ->where('created_at', '>=', now()->subDays(30))
                        ->where('status', '!=', AppointmentStatus::Cancelled->value)
                        ->count();

                    return max($monthlyLimit - $used, 0);
                });

                return $result;
            }
        )->shouldCache();
    }

    /**
     * Monthly appointment quota, prioritizing the company plan (CompanyPlan)
     * over the individual subscription when both exist.
     */
    private function resolveMonthlyAppointmentLimit(): int
    {
        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $this->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active->value)
            ->whereNull('deleted_at')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();

        if ($contractualPlan !== null) {
            return (int) $contractualPlan->monthly_appointments_per_employee;
        }

        /** @var Subscription|null $subscription */
        $subscription = $this->activeSubscription()->with('price')->first();

        return (int) ($subscription?->price->monthly_appointments ?? 0);
    }

    /**
     * @return MorphOne<Subscription, $this>
     */
    public function activeSubscription(): MorphOne
    {
        return $this->morphOne(Subscription::class, 'subscriptionable')
            ->where('stripe_status', 'active');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('user_avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('user-avatar')
            ->performOnCollections('user_avatar')
            ->nonQueued()
            ->fit(Fit::Crop, 96, 96);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $media = $this->getFirstMedia('user_avatar');

        return $media?->getTemporaryUrl(
            now()->addMinutes(60),
            'user-avatar'
        );
    }
}
