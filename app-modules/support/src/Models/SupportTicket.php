<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

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
 * @property string|null $url
 * @property string|null $browser
 * @property string|null $device
 * @property string|null $environment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SupportTicket extends Model implements HasMedia
{
    use HasFactory;
    use HasUuids;
    use InteractsWithMedia;

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
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'application/pdf',
            ]);
    }

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

    public function getRequesterEmail(): ?string
    {
        return $this->user?->email ?? $this->visitor_email;
    }

    public function getRequesterName(): ?string
    {
        return $this->user?->name ?? $this->visitor_name;
    }
}
