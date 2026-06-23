<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;

/**
 * @property string $id
 * @property string $support_ticket_id
 * @property TicketDestinationTypeEnum $type
 * @property TicketDestinationChannelEnum $channel
 * @property string|null $reference_id
 * @property TicketDestinationStatusEnum $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TicketDestination extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'type',
        'channel',
        'reference_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => TicketDestinationTypeEnum::class,
            'channel' => TicketDestinationChannelEnum::class,
            'status' => TicketDestinationStatusEnum::class,
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
