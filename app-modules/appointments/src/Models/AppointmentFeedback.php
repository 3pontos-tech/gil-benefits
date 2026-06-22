<?php

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Appointments\Database\Factories\AppointmentFeedbackFactory;

/**
 * @property int $id
 * @property string $appointment_id
 * @property string $user_id
 * @property int $rating
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AppointmentFeedback extends Model
{
    use HasFactory;

    protected $table = 'appointment_feedbacks';

    protected $fillable = [
        'appointment_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    protected static function newFactory(): AppointmentFeedbackFactory
    {
        return AppointmentFeedbackFactory::new();
    }

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
