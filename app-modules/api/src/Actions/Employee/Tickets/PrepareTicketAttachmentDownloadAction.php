<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Tickets;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Api\DTOs\Employee\Tickets\TicketAttachmentDownload;
use TresPontosTech\Api\Support\DownloadFilename;
use TresPontosTech\Support\Models\SupportTicket;

final readonly class PrepareTicketAttachmentDownloadAction
{
    public function __construct(private ListEmployeeTicketsAction $listTickets) {}

    /**
     * Um link do armazenamento que vale `api.tickets.attachment_download_ttl_seconds` e já baixa
     * o anexo com o nome com que foi enviado, como o dos materiais. Só sai para a pessoa do chamado: chamado de
     * outra pessoa, ou anexo que não é dele, responde como inexistente.
     *
     * @throws ModelNotFoundException<SupportTicket>
     */
    public function handle(User $user, string $ticketId, string $attachmentId): TicketAttachmentDownload
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->listTickets->handle($user)->findOrFail($ticketId);
        $media = $ticket->getMedia('attachments')->firstWhere('uuid', $attachmentId);

        throw_unless($media instanceof Media, ModelNotFoundException::class);

        $expiresAt = now()->addSeconds((int) config('api.tickets.attachment_download_ttl_seconds'));
        $filename = DownloadFilename::ofMedia($media);

        return new TicketAttachmentDownload(
            url: $media->getTemporaryUrl($expiresAt, '', [
                'ResponseContentDisposition' => DownloadFilename::disposition($filename, 'anexo'),
                'ResponseContentType' => $media->mime_type,
            ]),
            filename: $filename,
            mimeType: (string) $media->mime_type,
            size: (int) $media->size,
            expiresAt: $expiresAt,
        );
    }
}
