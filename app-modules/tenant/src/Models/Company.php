<?php

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Plans\Models\Item;
use TresPontosTech\Tenant\Database\Factories\CompanyFactory;
use TresPontosTech\Tenant\Policies\CompanyPolicy;
use TresPontosTech\Vouchers\Models\Voucher;
use TresPontosTech\Vouchers\Models\VoucherRequest;

#[UsePolicy(CompanyPolicy::class)]
class Company extends Model
{
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
            ->withPivot('role')
            ->withTimestamps();
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->BelongsToMany(Item::class, 'company_plans', 'company_id', 'item_id')->withPivot('status')->withTimestamps();
    }

    public function voucherRequests(): HasMany
    {
        return $this->hasMany(VoucherRequest::class);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
