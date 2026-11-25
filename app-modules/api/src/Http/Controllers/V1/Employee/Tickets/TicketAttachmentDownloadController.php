<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Tickets;

use App\Models\Users\User;
use Illuminate\Http\Request;
use TresPontosTech\Api\Actions\Employee\Tickets\PrepareTicketAttachmentDownloadAction;
use TresPontosTech\Api\Http\Resources\V1\Employee\Tickets\TicketAttachmentDownloadResource;

class TicketAttachmentDownloadController
{
    /**
     * O app pede o link na hora de mostrar, abrir ou compartilhar um anexo, e usa o arquivo baixado.
     */
    public function __invoke(
        Request $request,
        string $ticket,
        string $attachment,
        PrepareTicketAttachmentDownloadAction $prepareDownload,
    ): TicketAttachmentDownloadResource {
        /** @var User $user */
        $user = $request->user();

        return TicketAttachmentDownloadResource::make($prepareDownload->handle($user, $ticket, $attachment));
    }
}
