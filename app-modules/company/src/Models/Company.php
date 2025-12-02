<?php

namespace TresPontosTech\Company\Models;

use App\Models\Concerns\HasOptimizedQueries;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Database\Factories\CompanyFactory;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Policies\CompanyPolicy;

#[UsePolicy(CompanyPolicy::class)]
class Company extends Model
{
    use Billable;
    use HasFactory;
    use HasOptimizedQueries;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tax_id',
        'partner_code',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hasActivePlan(): bool
    {
        return $this->plans()->wherePivot('status', 'active')->exists();
    }

    /**
     * Scope to eager load common relationships to prevent N+1 queries.
     */
    public function scopeWithCommonRelations(Builder $query): void
    {
        $query->with([
            'owner:id,name,email',
            'employees:id,name,email',
            'subscriptions' => function ($query) {
                $query->where('stripe_status', 'active')
                    ->latest();
            },
        ]);
    }

    /**
     * Scope to load only partner companies.
     */
    public function scopePartners(Builder $query): void
    {
        $query->whereNotNull('partner_code');
    }

    /**
     * Scope to load companies with active subscriptions.
     */
    public function scopeWithActiveSubscriptions(Builder $query): void
    {
        $query->whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        });
    }

    public static function findByPartnerCode(string $code): ?self
    {
        return static::where('partner_code', $code)->first();
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
}
