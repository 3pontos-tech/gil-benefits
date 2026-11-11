<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Um envio aceito pela Expo, à espera do recibo. O id é o do ticket que a Expo devolveu.
 *
 * @property string $id
 * @property string $push_token_id
 * @property Carbon $created_at
 * @property-read PushToken $pushToken
 */
final class PushTicket extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'push_tickets';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'push_token_id',
        'created_at',
    ];

    /**
     * @return BelongsTo<PushToken, $this>
     */
    public function pushToken(): BelongsTo
    {
        return $this->belongsTo(PushToken::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
