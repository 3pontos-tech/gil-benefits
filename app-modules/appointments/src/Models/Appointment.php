<?php

namespace TresPontosTech\Appointments\Models;

use App\Models\Concerns\HasOptimizedQueries;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Policies\AppointmentPolicy;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

#[UsePolicy(AppointmentPolicy::class)]
class Appointment extends Model
{
    use HasFactory;
    use HasOptimizedQueries;

    protected $fillable = [
        'user_id',
        'consultant_id',
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

    /**
     * Scope to eager load common relationships to prevent N+1 queries.
     */
    public function scopeWithCommonRelations(Builder $query): void
    {
        $query->with([
            'user:id,name,email',
            'consultant:id,name,email,slug',
            'company:id,name,slug',
        ]);
    }

    /**
     * Scope to filter appointments by status.
     */
    public function scopeByStatus(Builder $query, AppointmentStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope to get upcoming appointments.
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('appointment_at', '>', now())
            ->orderBy('appointment_at', 'asc');
    }

    /**
     * Scope to get past appointments.
     */
    public function scopePast(Builder $query): void
    {
        $query->where('appointment_at', '<', now())
            ->orderBy('appointment_at', 'desc');
    }

    /**
     * Scope to get appointments within date range.
     */
    public function scopeInDateRange(Builder $query, \Carbon\Carbon $start, \Carbon\Carbon $end): void
    {
        $query->whereBetween('appointment_at', [$start, $end]);
    }

    /**
     * Scope to get ongoing appointments (not completed or cancelled).
     */
    public function scopeOngoing(Builder $query): void
    {
        $query->whereNotIn('status', [
            AppointmentStatus::Completed,
            AppointmentStatus::Cancelled,
        ]);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
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
