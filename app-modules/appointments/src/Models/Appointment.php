<?php

namespace TresPontosTech\Appointments\Models;

use App\Models\Consultant;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Tenant\Models\Company;
use TresPontosTech\Vouchers\Models\Voucher;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consultant_id',
        'voucher_id',
        'external_opportunity_id',
        'external_appointment_id',
        'category_type',
        'company_id',
        'appointment_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'category_type' => AppointmentCategoryEnum::class,
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
