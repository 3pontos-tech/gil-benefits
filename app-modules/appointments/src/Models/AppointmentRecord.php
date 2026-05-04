<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Appointments\Database\Factories\AppointmentRecordFactory;
use TresPontosTech\Appointments\Policies\AppointmentRecordPolicy;

#[UsePolicy(AppointmentRecordPolicy::class)]
class AppointmentRecord extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'appointment_id',
        'content',
        'internal_summary',
        'model_used',
        'input_tokens',
        'output_tokens',
        'generation_started_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'generation_started_at' => 'datetime',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }

    protected static function newFactory(): AppointmentRecordFactory
    {
        return AppointmentRecordFactory::new();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function markGenerationStarted(): void
    {
        $this->update(['generation_started_at' => now()]);
    }

    public function clearGenerationStart(): void
    {
        $this->update(['generation_started_at' => null]);
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
