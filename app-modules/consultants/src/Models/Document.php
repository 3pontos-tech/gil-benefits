<?php

namespace TresPontosTech\Consultants\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use TresPontosTech\Consultants\Database\Factories\DocumentFactory;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Policies\DocumentPolicy;

/**
 * @property string $id
 * @property string|null $documentable_type
 * @property string|null $documentable_id
 * @property string $title
 * @property DocumentExtensionTypeEnum|null $type
 * @property bool $active
 * @property string|null $link
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Consultant|User|null $documentable
 */
#[UsePolicy(DocumentPolicy::class)]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'active',
        'documentable_type',
        'documentable_id',
        'link',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'bool',
            'type' => DocumentExtensionTypeEnum::class,
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<DocumentShare, $this>
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

    public function hasLink(): bool
    {
        return $this->type === DocumentExtensionTypeEnum::Link && filled($this->link);
    }
}
