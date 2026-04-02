<?php

namespace TresPontosTech\Appointments\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Appointments\Database\Factories\AppointmentFeedbackFactory;

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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
