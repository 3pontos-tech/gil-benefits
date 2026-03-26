<?php

namespace TresPontosTech\Consultants\Models;

use App\Enums\AvailableTagsEnum;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Observers\ConsultantObserver;
use TresPontosTech\Consultants\Policies\ConsultantPolicy;
use Zap\Models\Concerns\HasSchedules;

/**
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string $short_description
 * @property string $biography
 * @property string $readme
 * @property array $socials_urls
 * @property string|null $crm_id
 * @property Carbon|null $google_calendar_synced_at
 * @property-read User|null $user
 */
#[ObservedBy(ConsultantObserver::class)]
#[UsePolicy(ConsultantPolicy::class)]
class Consultant extends Model implements HasMedia
{
    use HasFactory;
    use HasSchedules;
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
        'crm_id',
        'google_calendar_synced_at',
        'user_id',
    ];

    protected $casts = [
        'socials_urls' => 'array',
        'google_calendar_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function clients(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Appointment::class, 'consultant_id', 'id', 'id', 'user_id');
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
