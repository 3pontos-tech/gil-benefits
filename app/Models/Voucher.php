<?php

namespace App\Models;

use App\Enums\VoucherStatusEnum;
use App\Models\Companies\Company;
use App\Models\Users\User;
use App\Policies\VoucherPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(VoucherPolicy::class)]
class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'company_id',
        'consultant_id',
        'user_id',
        'status',
        'valid_until',
    ];

    public function casts(): array
    {
        return [
            'valid_until' => 'datetime',
            'status' => VoucherStatusEnum::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
