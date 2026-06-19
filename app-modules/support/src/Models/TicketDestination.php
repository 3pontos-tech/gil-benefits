<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;

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
