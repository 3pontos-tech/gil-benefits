<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee\Tickets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Api\Support\DownloadFilename;

/**
 * Um anexo do chamado com o nome com que foi enviado, sem link: o arquivo sai só em
 * `POST tickets/{id}/attachments/{anexo}/download`, como os materiais.
 *
 * @mixin Media
 *
 * @property Media $resource
 */
class TicketAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => DownloadFilename::ofMedia($this->resource),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
        ];
    }
}
