<?php

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use TresPontosTech\Tenant\Enums\CompanyRoleEnum;

class TenantMember extends Pivot
{

    public $timestamps = true;

    protected $fillable = [
        'role'
    ];

    protected $casts = [
        'role' => CompanyRoleEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
