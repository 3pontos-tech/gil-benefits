<?php

namespace TresPontosTech\Consultants\Models;

use App\Enums\AvailableTagsEnum;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Consultants\Database\Factories\ConsultantFactory;
use TresPontosTech\Consultants\Observers\ConsultantObserver;
use TresPontosTech\Consultants\Policies\ConsultantPolicy;
use Zap\Models\Concerns\HasSchedules;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $crm_id
 * @property string $name
 * @property string $slug
 * @property string $phone
 * @property string $email
 * @property string $short_description
 * @property string $biography
 * @property string $readme
 * @property array<string, string> $socials_urls
 * @property Carbon|null $google_calendar_synced_at
 * @property string|null $google_calendar_sync_token
 * @property Carbon|null $last_full_sync_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read int|null $completed_count
 * @property-read User|null $user
 */
#[ObservedBy(ConsultantObserver::class)]
#[UseFactory(ConsultantFactory::class)]
#[UsePolicy(ConsultantPolicy::class)]
class Consultant extends Model implements HasMedia
{
    /** @use HasFactory<ConsultantFactory> */
    use HasFactory;

    use HasSchedules;
    use HasTags;
    use HasUuids;
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
        'crm_id',
        'google_calendar_synced_at',
        'google_calendar_sync_token',
        'last_full_sync_at',
        'user_id',
    ];

    protected $casts = [
        'socials_urls' => 'array',
        'google_calendar_synced_at' => 'datetime',
        'last_full_sync_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasManyThrough<User, Appointment, $this>
     */
    public function clients(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Appointment::class, 'consultant_id', 'id', 'id', 'user_id');
    }

    /**
     * @return HasManyThrough<AppointmentFeedback, Appointment, $this>
     */
    public function feedbacks(): HasManyThrough
    {
        return $this->hasManyThrough(AppointmentFeedback::class, Appointment::class);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function languages(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Language->value);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function degrees(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Education->value);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function expertises(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Expertise->value);
    }

    /**
     * @return MorphToMany<Tag, $this>
     */
    public function specializations(): MorphToMany
    {
        return $this->tags()
            ->where('type', AvailableTagsEnum::Specialization->value);
    }
}
