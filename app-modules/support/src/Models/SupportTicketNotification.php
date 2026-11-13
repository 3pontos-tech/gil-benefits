<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use TresPontosTech\Support\Database\Factories\SupportTicketNotificationFactory;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Models\Builders\SupportTicketNotificationBuilder;

/**
 * @property string $id
 * @property string $support_ticket_id
 * @property TicketNotificationChannelEnum $channel
 * @property TicketNotificationEventEnum $event
 * @property SupportTicketStatusEnum|null $from_status
 * @property SupportTicketStatusEnum $to_status
 * @property string $message
 * @property TicketNotificationStatusEnum $status
 * @property int $attempts
 * @property int|null $response_code
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SupportTicket $ticket
 *
 * @method static SupportTicketNotificationBuilder query()
 */
#[UseFactory(SupportTicketNotificationFactory::class)]
#[UseEloquentBuilder(SupportTicketNotificationBuilder::class)]
#[Table(name: 'support_ticket_notifications')]
class SupportTicketNotification extends Model
{
    /** @use HasFactory<SupportTicketNotificationFactory> */
    use HasFactory;

    use HasUuids;

    private const int ERROR_LIMIT = 1000;

    protected $fillable = [
        'support_ticket_id',
        'channel',
        'event',
        'from_status',
        'to_status',
        'message',
        'status',
        'attempts',
        'response_code',
        'error',
        'sent_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'attempts' => 0,
    ];

    protected function casts(): array
    {
        return [
            'channel' => TicketNotificationChannelEnum::class,
            'event' => TicketNotificationEventEnum::class,
            'from_status' => SupportTicketStatusEnum::class,
            'to_status' => SupportTicketStatusEnum::class,
            'status' => TicketNotificationStatusEnum::class,
            'attempts' => 'integer',
            'response_code' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === TicketNotificationStatusEnum::Pending;
    }

    public function markSent(int $responseCode): void
    {
        $this->update([
            'status' => TicketNotificationStatusEnum::Sent,
            'attempts' => $this->attempts + 1,
            'response_code' => $responseCode,
            'error' => null,
            'sent_at' => now(),
        ]);
    }

    public function recordFailedAttempt(?int $responseCode, string $error): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'response_code' => $responseCode,
            'error' => Str::limit($error, self::ERROR_LIMIT),
        ]);
    }

    public function markSkipped(string $reason): void
    {
        $this->update([
            'status' => TicketNotificationStatusEnum::Skipped,
            'error' => Str::limit($reason, self::ERROR_LIMIT),
        ]);
    }

    public function requeue(): bool
    {
        if ($this->status !== TicketNotificationStatusEnum::Failed) {
            return false;
        }

        return $this->update(['status' => TicketNotificationStatusEnum::Pending]);
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
