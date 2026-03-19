<?php

namespace App\Models\Users;

use App\Filament\FilamentPanel;
use App\Observers\UserObserver;
use App\Policies\Users\UserPolicy;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Models\Traits\HasTenant;

#[UsePolicy(UserPolicy::class)]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use Billable;
    use HasFactory;
    use HasRoles;
    use HasTenant;
    use HasUuids;
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

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->companies()->whereKey($tenant)->exists();
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_employees', 'user_id', 'company_id')
            ->withTimestamps()
            ->withPivot(['role'])
            ->using(TenantMember::class);
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(Detail::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scheduledAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    public function activeSubscription(): MorphOne
    {
        return $this->morphOne(Subscription::class, 'subscriptionable')
            ->where('stripe_status', 'active');
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
            ])
            ->exists();
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
        return $this->monthly_appointments_left > 0 && ! $this->hasOngoingAppointment();
    }

    /**
     * Computed, cached monthly appointments left in the last 30 days window.
     */
    protected function monthlyAppointmentsLeft(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->getKey() === null) {
                    return 0;
                }

                $cacheKey = $this->getMonthlyAppointmentsLeftCacheKey();

                return Cache::remember($cacheKey, now()->addMinutes(5), function (): int {
                    $subscription = $this->activeSubscription()
                        ->with('price')
                        ->first();

                    if ($subscription === null || $subscription->price === null) {
                        $contractualPlan = $this->companies()
                            ->get()
                            ->map(fn ($company) => $company->activeContractualPlan())
                            ->filter()
                            ->first();

                        if ($contractualPlan === null) {
                            return 0;
                        }

                        $monthlyLimit = (int) ($contractualPlan->monthly_appointments_per_employee ?? 0);
                        if ($monthlyLimit <= 0) {
                            return 0;
                        }

                        $since = now()->subDays(30);
                        $used = (int) $this->appointments()->where('created_at', '>=', $since)->count();

                        return max($monthlyLimit - $used, 0);
                    }

                    $monthlyLimit = (int) ($subscription->price->monthly_appointments ?? 0);
                    if ($monthlyLimit <= 0) {
                        return 0;
                    }

                    $since = now()->subDays(30);

                    $used = (int) $this->appointments()
                        ->where('created_at', '>=', $since)
                        ->count();

                    return max($monthlyLimit - $used, 0);
                });
            }
        )->shouldCache();
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([Roles::SuperAdmin, Roles::Admin]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Roles::SuperAdmin);
    }

    public function isCompanyOwner(): bool
    {
        return $this->hasRole([Roles::CompanyOwner]);
    }

    public function isEmployee(): bool
    {
        return $this->hasRole(Roles::Employee);
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
        return sprintf('user:%d:monthly_appointments_left', $this->getKey());
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
}
