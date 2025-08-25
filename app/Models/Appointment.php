<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'voucher_id',
        'date',
        'status',
    ];

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }
}
