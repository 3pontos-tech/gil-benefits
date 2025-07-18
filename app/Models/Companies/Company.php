<?php

namespace App\Models\Companies;

use App\Models\Users\User;
use App\Models\Voucher;
use App\Policies\Companies\CompanyPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(CompanyPolicy::class)]
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'tax_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_employees', 'company_id', 'user_id')->withTimestamps();
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
