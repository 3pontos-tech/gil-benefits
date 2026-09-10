<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Database\Factories\VoucherRedemptionFactory;

/**
 * @property string $id
 * @property string $voucher_code_id
 * @property string $user_id
 * @property Carbon $redeemed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(VoucherRedemptionFactory::class)]
class VoucherRedemption extends Model
{
    /** @use HasFactory<VoucherRedemptionFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'voucher_code_id',
        'user_id',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VoucherCode, $this>
     */
    public function code(): BelongsTo
    {
        return $this->belongsTo(VoucherCode::class, 'voucher_code_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<UserCredit, $this>
     */
    public function credits(): HasMany
    {
        return $this->hasMany(UserCredit::class, 'voucher_redemption_id');
    }
}
