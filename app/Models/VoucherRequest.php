<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Tenant\Models\Company;

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
}
