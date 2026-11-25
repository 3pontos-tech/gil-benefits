<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee\Tickets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\DTOs\Employee\Tickets\TicketAttachmentDownload;
use TresPontosTech\Api\Support\ApiDates;

/**
 * @mixin TicketAttachmentDownload
 */
class TicketAttachmentDownloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => $this->url,
            'filename' => $this->filename,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'expires_at' => ApiDates::dateTime($this->expiresAt),
        ];
    }
}
