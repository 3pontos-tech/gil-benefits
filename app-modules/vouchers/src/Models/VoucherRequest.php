<?php

namespace TresPontosTech\Vouchers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Tenant\Models\Company;
use TresPontosTech\Vouchers\Database\Factories\VoucherRequestFactory;

class VoucherRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'quantity',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): VoucherRequestFactory
    {
        return VoucherRequestFactory::new();
    }
}
