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
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;

/**
 * @property string $id
 * @property AppointmentHistoryActionType $action_type
 * @property string $appointment_id
 * @property string $actor_id
 * @property AppointmentHistoryActor $actor_type
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Appointment $appointment
 * @property-read User $author
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
        'actor_id',
        'actor_type',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'action_type' => AppointmentHistoryActionType::class,
            'actor_type' => AppointmentHistoryActor::class,
        ];
    }
}
