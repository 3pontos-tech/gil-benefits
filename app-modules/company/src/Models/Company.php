<?php

namespace TresPontosTech\Company\Models;

use App\Models\Users\User;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Ramsey\Uuid\Uuid;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Database\Factories\CompanyFactory;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\Credits\Observers\CompanyCreditsObserver;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Policies\CompanyPolicy;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $integration_access_key
 * @property string $slug
 * @property string $tax_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $trial_ends_at
 * @property string $panel
 */
#[ObservedBy(CompanyCreditsObserver::class)]
#[UseFactory(CompanyFactory::class)]
#[UsePolicy(CompanyPolicy::class)]
class Company extends Model implements HasAvatar, HasMedia
{
    use Billable;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * Slug of the shared company that holds every user without an employer:
     * B2C subscribers, consultants and admin-created users. It is a bucket, not
     * a client — its seats are a synthetic "unlimited" subscription and
     * subscription enforcement is skipped for it. Because everyone belongs to
     * it, it is never on its own a reliable answer to "which company is this
     * person from" — see User::employerCompanyId().
     *
     * @see AttachToDefaultCompany
     */
    public const string DEFAULT_SLUG = 'flamma-company';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tax_id',
        'integration_access_key',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Se esta empresa banca parte da mensalidade dos colaboradores.
     *
     * O tenant default é o balde de quem não tem empregador, então ninguém
     * subsidia nada ali: aqueles usuários pagam o valor cheio. É o que decide
     * qual audiência de preço vale no checkout (PriceAudienceEnum).
     */
    public function subsidizesEmployees(): bool
    {
        return $this->slug !== self::DEFAULT_SLUG;
    }

    public function hasActivePlan(): bool
    {
        return filled($this->activeContractualPlan());
    }

    public function activeContractualPlan(): ?CompanyPlan
    {
        return CompanyPlan::query()
            ->where('company_id', $this->id)
            ->activeOn()
            ->first();
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasManyThrough<AppointmentFeedback, Appointment, $this>
     */
    public function feedbacks(): HasManyThrough
    {
        return $this->hasManyThrough(AppointmentFeedback::class, Appointment::class);
    }

    /**
     * @return HasMany<CompanyPlan, $this>
     */
    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class);
    }

    /**
     * @return HasMany<CreditGrant, $this>
     */
    public function creditGrants(): HasMany
    {
        return $this->hasMany(CreditGrant::class)->latest();
    }

    /**
     * @return BelongsToMany<Plan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'company_plans', 'company_id', 'plan_id')
            ->withTimestamps()
            ->withPivot(['seats', 'monthly_appointments_per_employee', 'status', 'starts_at', 'ends_at', 'notes'])
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany<User, $this, TenantMember>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_employees', 'company_id', 'user_id')
            ->withTimestamps()
            ->withPivot(['role', 'active', 'department_id'])
            ->using(TenantMember::class);
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * @return BelongsToMany<User, $this, TenantMember>
     */
    #[Scope]
    protected function onlyEmployees(): BelongsToMany
    {
        return $this->employees()->wherePivot('active', true)->whereNot('id', $this->user_id);
    }

    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    /**
     * Exclui a empresa-balde que abriga os assinantes avulsos.
     *
     * Ela não é cliente: entrar num ranking de receita por empresa a deixaria em
     * primeiro lugar permanentemente e dispararia o alerta de concentração todo
     * mês sem significar nada (FLM-41, D-11).
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withoutDefault(Builder $query): void
    {
        $query->whereNot('slug', self::DEFAULT_SLUG);
    }

    public function generateToken(Uuid|string $key): void
    {
        $this->update(['integration_access_key' => $key]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company_logo')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('company-logo-avatar')
            ->performOnCollections('company_logo')
            ->nonQueued()
            ->width(32)
            ->height(32)
            ->fit(Fit::Crop, 32, 32);

    }

    public function getFilamentAvatarUrl(): ?string
    {
        $media = $this->getFirstMedia('company_logo');

        $media = $media?->getTemporaryUrl(
            now()->addMinutes(60),
            'company-logo-avatar'
        );

        return $media ?: null;
    }
}
