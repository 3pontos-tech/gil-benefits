<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Support\Database\Factories\SupportTicketStatusChangeFactory;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;

/**
 * Insert-only: a row is the record of a transition that already happened, so it
 * has no updated_at and is never edited.
 *
 * @property string $id
 * @property string $support_ticket_id
 * @property SupportTicketStatusEnum|null $from_status
 * @property SupportTicketStatusEnum $to_status
 * @property TicketStatusChangeSourceEnum $source
 * @property string|null $actor_user_id
 * @property string|null $reason
 * @property Carbon|null $created_at
 * @property-read SupportTicket $ticket
 * @property-read User|null $actor
 */
#[UseFactory(SupportTicketStatusChangeFactory::class)]
#[Table(name: 'support_ticket_status_changes')]
class SupportTicketStatusChange extends Model
{
    /** @use HasFactory<SupportTicketStatusChangeFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'support_ticket_id',
        'from_status',
        'to_status',
        'source',
        'actor_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SupportTicketStatusEnum::class,
            'to_status' => SupportTicketStatusEnum::class,
            'source' => TicketStatusChangeSourceEnum::class,
        ];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * withTrashed: the history keeps naming who made a change after that account is deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }
}
