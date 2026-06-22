<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Actions\Transitions\AbstractAppointmentTransition;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Billing\Core\Models\UserCredit;
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

    public function credit(): HasOne
    {
        return $this->hasOne(UserCredit::class);
    }

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

    #[Scope]
    protected function forCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->getKey());
    }

    #[Scope]
    protected function betweenDates(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereBetween('appointment_at', [$start, $end]);
    }

    #[Scope]
    protected function forUsers(Builder $query, ?Collection $userIds): Builder
    {
        if (! $userIds instanceof Collection) {
            return $query;
        }

        return $query->whereIn('user_id', $userIds);
    }
}
