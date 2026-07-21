<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Appointments\Database\Factories\AppointmentHistoryFactory;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;

/**
 * @property string $id
 * @property AppointmentHistoryActionType $action_type
 * @property string $appointment_id
 * @property string $admin_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Appointment $appointment
 * @property-read User $admin
 */
#[UseFactory(AppointmentHistoryFactory::class)]
class AppointmentHistory extends Model
{
    /** @use HasFactory<AppointmentHistoryFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'action_type',
        'appointment_id',
        'admin_id',
        'old_values',
        'new_values',
    ];

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'action_type' => AppointmentHistoryActionType::class,
        ];
    }
}
