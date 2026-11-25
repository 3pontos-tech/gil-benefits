<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Support\Database\Factories\SupportTicketFactory;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketChannelEnum;
use TresPontosTech\Support\Models\Builders\SupportTicketBuilder;

/**
 * @property string $id
 * @property string $protocol
 * @property string|null $user_id
 * @property string|null $company_id
 * @property string|null $visitor_name
 * @property string|null $visitor_email
 * @property string|null $visitor_company_name
 * @property SupportTicketCategoryEnum $category
 * @property string $subject
 * @property string $description
 * @property SupportTicketStatusEnum $status
 * @property TicketChannelEnum $channel
 * @property string|null $url
 * @property string|null $browser
 * @property string|null $device
 * @property string|null $environment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TicketOrigin|null $origin
 * @property-read Collection<int, SupportTicketStatusChange> $statusChanges
 *
 * @method static SupportTicketBuilder query()
 */
#[UseFactory(SupportTicketFactory::class)]
#[UseEloquentBuilder(SupportTicketBuilder::class)]
#[Table(name: 'support_tickets')]
class SupportTicket extends Model implements HasMedia
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithMedia;

    /**
     * The only types the `attachments` collection takes. A form that let another one through
     * would find out only after the ticket was written, so the API validates against this list.
     */
    public const array ATTACHMENT_MIME_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    protected $fillable = [
        'protocol',
        'user_id',
        'company_id',
        'visitor_name',
        'visitor_email',
        'visitor_company_name',
        'category',
        'subject',
        'description',
        'status',
        'channel',
        'url',
        'browser',
        'device',
        'environment',
    ];

    protected function casts(): array
    {
        return [
            'category' => SupportTicketCategoryEnum::class,
            'status' => SupportTicketStatusEnum::class,
            'channel' => TicketChannelEnum::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(self::ATTACHMENT_MIME_TYPES);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(TicketDestination::class);
    }

    /**
     * @return HasMany<SupportTicketNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(SupportTicketNotification::class);
    }

    /**
     * @return HasMany<SupportTicketStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(SupportTicketStatusChange::class);
    }

    /**
     * The external system this ticket came in from, when it did not start on the
     * platform. Null for tickets opened in the panel or the public help center.
     *
     * @return HasOne<TicketOrigin, $this>
     */
    public function origin(): HasOne
    {
        return $this->hasOne(TicketOrigin::class);
    }

    public function getRequesterEmail(): ?string
    {
        return $this->user?->email ?? $this->visitor_email;
    }

    public function getRequesterName(): ?string
    {
        return $this->user?->name ?? $this->visitor_name;
    }
}
