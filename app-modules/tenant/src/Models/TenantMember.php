<?php

namespace TresPontosTech\Tenant\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

class TenantMember extends Pivot
{
    public $timestamps = true;

    protected $fillable = [
        'role',
        'active',
    ];

    protected $casts = [
        'role' => Roles::class,
        'active' => 'boolean',
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
