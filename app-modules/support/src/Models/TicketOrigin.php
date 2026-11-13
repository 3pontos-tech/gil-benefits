<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Support\Database\Factories\TicketOriginFactory;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;

/**
 * @property string $id
 * @property string $support_ticket_id
 * @property TicketOriginSourceEnum $source
 * @property string $external_reference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(TicketOriginFactory::class)]
class TicketOrigin extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'source',
        'external_reference',
    ];

    protected function casts(): array
    {
        return [
            'source' => TicketOriginSourceEnum::class,
        ];
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
