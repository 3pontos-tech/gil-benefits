<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use TresPontosTech\Vouchers\Database\Factories\VoucherCodeFactory;

/**
 * @property string $id
 * @property string $voucher_batch_id
 * @property string $code
 * @property int $max_redemptions
 * @property int $redemptions_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(VoucherCodeFactory::class)]
class VoucherCode extends Model
{
    /** @use HasFactory<VoucherCodeFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'voucher_batch_id',
        'code',
        'max_redemptions',
        'redemptions_count',
    ];

    protected function casts(): array
    {
        return [
            'max_redemptions' => 'integer',
            'redemptions_count' => 'integer',
        ];
    }

    public function isExhausted(): bool
    {
        return $this->redemptions_count >= $this->max_redemptions;
    }

    /**
     * @return BelongsTo<VoucherBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    /**
     * @return HasMany<VoucherRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
