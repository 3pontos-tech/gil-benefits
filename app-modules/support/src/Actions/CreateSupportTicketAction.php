<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use Illuminate\Http\UploadedFile;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Jobs\DispatchSupportTicketJob;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\ProtocolGenerator;

class CreateSupportTicketAction
{
    public function __construct(
        private readonly ProtocolGenerator $protocols,
    ) {}

    public function execute(CreateSupportTicketDTO $dto): SupportTicket
    {
        $ticket = SupportTicket::query()->create([
            'protocol' => $this->protocols->generate(),
            'user_id' => $dto->userId,
            'company_id' => $dto->companyId,
            'visitor_name' => $dto->visitorName,
            'visitor_email' => $dto->visitorEmail,
            'visitor_company_name' => $dto->visitorCompanyName,
            'category' => $dto->category,
            'subject' => $dto->subject,
            'description' => $dto->description,
            'status' => SupportTicketStatusEnum::Pending,
            'url' => $dto->url,
            'browser' => $dto->browser,
            'device' => $dto->device,
            'environment' => $dto->environment,
        ]);

        if ($dto->attachment instanceof UploadedFile) {
            $ticket->addMedia($dto->attachment)
                ->toMediaCollection('attachments');
        }

        dispatch(new DispatchSupportTicketJob($ticket));

        return $ticket;
    }
}
