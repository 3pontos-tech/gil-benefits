<?php

namespace TresPontosTech\Consultants\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Policies\DocumentPolicy;

#[UsePolicy(DocumentPolicy::class)]
class Document extends Model implements HasMedia
{
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'consultant_id',
        'title',
        'type',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'bool',
            'type' => DocumentExtensionTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Consultant, $this>
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class)->withTrashed();
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
}
