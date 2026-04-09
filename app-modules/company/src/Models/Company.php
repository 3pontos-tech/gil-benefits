<?php

namespace TresPontosTech\Company\Models;

use App\Models\Users\User;
use Filament\Models\Contracts\HasAvatar;
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
use Laravel\Cashier\Billable;
use Ramsey\Uuid\Uuid;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Database\Factories\CompanyFactory;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Policies\CompanyPolicy;

/**
 * @property string $user_id
 * @property string $panel
 * @property string $slug
 * @property string $tax_id
 * @property string $integration_access_key
 */
#[UsePolicy(CompanyPolicy::class)]
class Company extends Model implements HasAvatar, HasMedia
{
    use Billable;
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

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

    public function hasActivePlan(): bool
    {
        return filled($this->activeContractualPlan());
    }

    public function activeContractualPlan(): ?CompanyPlan
    {
        return CompanyPlan::query()->where('company_id', $this->id)
            ->where('status', CompanyPlanStatusEnum::Active->value)
            ->whereNull('deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function feedbacks(): HasManyThrough
    {
        return $this->hasManyThrough(AppointmentFeedback::class, Appointment::class);
    }

    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'company_plans', 'company_id', 'plan_id')
            ->withTimestamps()
            ->withPivot(['seats', 'monthly_appointments_per_employee', 'status', 'starts_at', 'ends_at', 'notes'])
            ->wherePivotNull('deleted_at');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_employees', 'company_id', 'user_id')
            ->withTimestamps()
            ->withPivot(['role', 'active'])
            ->using(TenantMember::class);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
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
            ->width(32)
            ->height(32)
            ->fit(Fit::Crop, 32, 32)
            ->nonQueued();

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
