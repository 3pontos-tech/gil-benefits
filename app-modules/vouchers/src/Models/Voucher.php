<?php

namespace TresPontosTech\Vouchers\Models;

use App\Enums\VoucherStatusEnum;
use App\Models\Users\User;
use App\Policies\VoucherPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Tenant\Models\Company;
use TresPontosTech\Vouchers\Database\Factories\VoucherFactory;

#[UsePolicy(VoucherPolicy::class)]
class Voucher extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'company_id',
        'consultant_id',
        'user_id',
        'status',
        'valid_until',
    ];

    public function markAsUsed(): void
    {
        $this->update(['status' => VoucherStatusEnum::Used]);
    }

    protected function casts(): array
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

    protected static function newFactory(): VoucherFactory
    {
        return VoucherFactory::new();
    }
}
