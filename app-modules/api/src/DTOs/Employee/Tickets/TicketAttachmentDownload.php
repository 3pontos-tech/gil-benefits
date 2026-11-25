<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Tickets;

use Carbon\CarbonInterface;

final readonly class TicketAttachmentDownload
{
    /**
     * Como o de um material: o link vale poucos minutos e só serve para o app baixar o arquivo
     * na hora; nunca é mostrado nem compartilhado.
     */
    public function __construct(
        public string $url,
        public string $filename,
        public string $mimeType,
        public int $size,
        public CarbonInterface $expiresAt,
    ) {}
}
