<?php

namespace TresPontosTech\Consultants\Models;

use App\Enums\AvailableTagsEnum;
use App\Models\Concerns\HasOptimizedQueries;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Policies\ConsultantPolicy;

#[UsePolicy(ConsultantPolicy::class)]
class Consultant extends Model implements HasMedia
{
    use HasFactory;
    use HasOptimizedQueries;
    use HasTags;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'short_description',
        'slug',
        'biography',
        'readme',
        'socials_urls',
        'external_id',
    ];

    protected function casts(): array
    {
        return [
            'socials_urls' => 'array',
        ];
    }

    /**
     * Scope to eager load common relationships to prevent N+1 queries.
     */
    public function scopeWithCommonRelations(Builder $query): void
    {
        $query->with([
            'languages',
            'degrees',
            'expertises',
            'specializations',
            'media',
        ]);
    }

    /**
     * Scope to load consultants with their appointment statistics.
     */
    public function scopeWithAppointmentStats(Builder $query): void
    {
        $query->withCount([
            'appointments',
            'appointments as completed_appointments_count' => function ($query) {
                $query->where('status', \TresPontosTech\Appointments\Enums\AppointmentStatus::Completed);
            },
        ]);
    }

    /**
     * Get all appointments for this consultant.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function languages(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Language->value);
    }

    public function degrees(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Education->value);
    }

    public function expertises(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Expertise->value);
    }

    public function specializations(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Specialization->value);
    }
}
