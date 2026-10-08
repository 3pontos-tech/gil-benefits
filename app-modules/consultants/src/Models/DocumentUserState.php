<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Quando a pessoa viu o material pela primeira vez e se o favoritou.
 *
 * @property string $id
 * @property string $document_id
 * @property string $user_id
 * @property Carbon|null $viewed_at
 * @property Carbon|null $favorited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DocumentUserState extends Model
{
    use HasUuids;

    protected $fillable = [
        'document_id',
        'user_id',
        'viewed_at',
        'favorited_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'favorited_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
