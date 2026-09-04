<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Actions\Transitions\AbstractAppointmentTransition;
use TresPontosTech\Appointments\Database\Factories\AppointmentFactory;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

/**
 * @property string $id
 * @property string|null $consultant_id
 * @property string $user_id
 * @property AppointmentCategoryEnum $category_type
 * @property Carbon $appointment_at
 * @property AppointmentStatus $status
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $company_id
 * @property string|null $meeting_url
 * @property string|null $google_event_id
 * @property string|null $cancelled_by
 * @property CancellationActor|null $cancellation_actor
 * @property Carbon|null $quota_refunded_at
 * @property-read AbstractAppointmentTransition $current_transition
 */
#[UseFactory(AppointmentFactory::class)]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * Notice a user must give to cancel without losing the credit or the monthly
     * quota. Public because the notices, e-mails and help hints that spell the
     * rule out quote this number without an appointment in hand.
     */
    public const CANCELLATION_WINDOW_HOURS = 4;

    /**
     * How much notice a user must give to move an appointment themselves. Below it the
     * reschedule button is gone and they have to cancel (paying the late penalty) or talk
     * to support. Deliberately its own constant: it happens to match the cancellation
     * window today, but the two answer different questions and may drift apart.
     */
    public const RESCHEDULE_WINDOW_HOURS = 4;

    /**
     * How many days ahead a booking must start. The pickers use it as minDate and the
     * server enforces it when listing and validating slots, so a forged request cannot
     * land earlier than the calendar allows.
     */
    public const BOOKING_LEAD_DAYS = 2;

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

    /**
     * @return BelongsTo<Consultant, $this>
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasOne<AppointmentFeedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(AppointmentFeedback::class);
    }

    /**
     * @return HasOne<AppointmentRecord, $this>
     */
    public function record(): HasOne
    {
        return $this->hasOne(AppointmentRecord::class);
    }

    /**
     * @return HasOne<UserCredit, $this>
     */
    public function credit(): HasOne
    {
        return $this->hasOne(UserCredit::class);
    }

    /**
     * @return HasMany<AppointmentHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(AppointmentHistory::class);
    }

    public function isActive(): bool
    {
        return $this->status === AppointmentStatus::Active;
    }

    /**
     * Whether cancelling right now would fall inside the penalty window: the user
     * loses the credit and the monthly quota. Admin and system cancellations are
     * never penalised, so they do not consult this.
     */
    public function isLateCancellation(): bool
    {
        return now()->diffInHours($this->appointment_at, absolute: false) < self::CANCELLATION_WINDOW_HOURS;
    }

    /**
     * Whether the user may still move this appointment themselves. Rescheduling drops the
     * consultant and sends the appointment back to Pending, so it is only offered while
     * there is enough notice to find another slot.
     */
    public function canBeRescheduled(): bool
    {
        return in_array($this->status, [AppointmentStatus::Pending, AppointmentStatus::Active], strict: true)
            && now()->diffInHours($this->appointment_at, absolute: false) >= self::RESCHEDULE_WINDOW_HOURS;
    }

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'category_type' => AppointmentCategoryEnum::class,
            'cancellation_actor' => CancellationActor::class,
            'quota_refunded_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<AbstractAppointmentTransition, never>
     */
    protected function currentTransition(): Attribute
    {
        return Attribute::make(get: fn (): AbstractAppointmentTransition => $this->status->transition($this));
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    #[Scope]
    protected function forCompany(Builder $query, Company $company): Builder
    {
        return $query->where('company_id', $company->getKey());
    }

    /**
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    #[Scope]
    protected function betweenDates(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereBetween('appointment_at', [$start, $end]);
    }

    /**
     * @param  Builder<Appointment>  $query
     * @param  Collection<int, string>|null  $userIds
     * @return Builder<Appointment>
     */
    #[Scope]
    protected function forUsers(Builder $query, ?Collection $userIds): Builder
    {
        if (! $userIds instanceof Collection) {
            return $query;
        }

        return $query->whereIn('user_id', $userIds);
    }
}
