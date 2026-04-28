<?php

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Appointments\Actions\Transitions\AbstractAppointmentTransition;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class Appointment extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'consultant_id',
        'external_opportunity_id',
        'external_appointment_id',
        'category_type',
        'company_id',
        'appointment_at',
        'status',
        'monday_item_id',
        'meeting_url',
        'google_event_id',
        'notes',
        'cancelled_by',
        'cancellation_actor',
    ];

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'category_type' => AppointmentCategoryEnum::class,
            'cancellation_actor' => CancellationActor::class,
        ];
    }

    protected function currentTransition(): Attribute
    {
        return Attribute::make(get: fn (): AbstractAppointmentTransition => $this->status->transition($this));
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(AppointmentFeedback::class);
    }

    public function record(): HasOne
    {
        return $this->hasOne(AppointmentRecord::class);
    }
}
