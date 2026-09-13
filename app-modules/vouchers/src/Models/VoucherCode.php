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
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Vouchers\Database\Factories\VoucherCodeFactory;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

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
class VoucherCode extends Model implements HasMedia
{
    public const QR_CODE_COLLECTION = 'qr-code';

    /** @use HasFactory<VoucherCodeFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithMedia;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::QR_CODE_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/svg+xml']);
    }

    public function isExhausted(): bool
    {
        return $this->redemptions_count >= $this->max_redemptions;
    }

    public function redemptionUrl(): string
    {
        return VoucherRedemptionUrl::for($this);
    }

    public function qrCode(): ?Media
    {
        return $this->getFirstMedia(self::QR_CODE_COLLECTION);
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
