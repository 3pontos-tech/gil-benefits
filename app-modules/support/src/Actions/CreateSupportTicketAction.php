<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Jobs\DispatchSupportTicketJob;
use TresPontosTech\Support\Mail\SupportTicketConfirmationMail;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\ProtocolGenerator;

class CreateSupportTicketAction
{
    public function __construct(
        private readonly ProtocolGenerator $protocols,
    ) {}

    public function execute(CreateSupportTicketDTO $dto): SupportTicket
    {
        $ticket = $this->createWithProtocol($dto);

        if ($dto->attachment instanceof UploadedFile) {
            $ticket->addMedia($dto->attachment)
                ->toMediaCollection('attachments');
        }

        // Acknowledge receipt to the requester once, regardless of the routing channel.
        $requesterEmail = $ticket->getRequesterEmail();
        if ($requesterEmail !== null) {
            Mail::to($requesterEmail)->queue(new SupportTicketConfirmationMail($ticket));
        }

        dispatch(new DispatchSupportTicketJob($ticket));

        return $ticket;
    }

    /**
     * Creates the ticket, generating the protocol and inserting in the same transaction.
     * Under a race two requests may read the same "last" value and generate the same
     * protocol; the UNIQUE constraint rejects the loser with 23505 and the retry reopens
     * the transaction, re-reads (now seeing the committed winning row) and takes the next
     * number.
     */
    private function createWithProtocol(CreateSupportTicketDTO $dto): SupportTicket
    {
        return retry(
            times: 5,
            callback: fn (): SupportTicket => DB::transaction(fn (): SupportTicket => SupportTicket::query()->create([
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
            ])),
            sleepMilliseconds: 20,
            when: static fn (Throwable $e): bool => $e instanceof QueryException
                && (int) ($e->errorInfo[0] ?? 0) === 23505,
        );
    }
}
