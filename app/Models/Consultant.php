<?php

namespace App\Models;

use App\Enums\AvailableTagsEnum;
use App\Policies\ConsultantPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

#[UsePolicy(ConsultantPolicy::class)]
class Consultant extends Model implements HasMedia
{
    use HasFactory;
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
    ];

    protected $casts = [
        'socials_urls' => 'array',
    ];

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

    public function appointments(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
