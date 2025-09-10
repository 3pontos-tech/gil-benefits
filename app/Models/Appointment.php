<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Companies\Company;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consultant_id',
        'voucher_id',
        'company_id',
        'date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
