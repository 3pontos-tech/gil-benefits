<?php

namespace TresPontosTech\Company\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Database\Factories\CompanyFactory;
use TresPontosTech\Plans\Models\Item;
use TresPontosTech\Tenant\Models\TenantMember;
use TresPontosTech\Tenant\Policies\CompanyPolicy;

#[UsePolicy(CompanyPolicy::class)]
class Company extends Model
{
    use Billable;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tax_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hasActivePlan(): bool
    {
        return $this->plans()->wherePivot('status', 'active')->exists();
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
