<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Actions;

use App\Models\Users\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\DTOs\TicketOriginDTO;
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

        foreach ($dto->attachments as $attachment) {
            $ticket->addMedia($attachment)
                ->toMediaCollection('attachments');
        }

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
     *
     * The origin row is written inside the same transaction so an integration-born
     * ticket can never exist without the external reference that identifies it. Its
     * UNIQUE is a last-resort guard: callers look the reference up before creating, so
     * it only fires when two deliveries race. The loser then burns the retries (the
     * duplicate cannot become insertable) and throws — which is the signal for the
     * caller to re-read and answer with the ticket that won.
     */
    private function createWithProtocol(CreateSupportTicketDTO $dto): SupportTicket
    {
        $userId = $dto->userId ?? $this->resolveUserIdByEmail($dto->visitorEmail);

        return retry(
            times: 5,
            callback: fn (): SupportTicket => DB::transaction(function () use ($dto, $userId): SupportTicket {
                $ticket = SupportTicket::query()->create([
                    'protocol' => $this->protocols->generate(),
                    'user_id' => $userId,
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

                if ($dto->origin instanceof TicketOriginDTO) {
                    $ticket->origin()->create([
                        'source' => $dto->origin->source,
                        'external_reference' => $dto->origin->externalReference,
                    ]);
                }

                return $ticket;
            }),
            sleepMilliseconds: 20,
            when: static fn (Throwable $e): bool => $e instanceof QueryException
                && (int) ($e->errorInfo[0] ?? 0) === 23505,
        );
    }

    /**
     * Tickets opened from the public help center carry only a visitor email. When that
     * email belongs to a registered user we attach the ticket to them, so it shows up
     * tied to their account instead of as an anonymous visitor submission.
     */
    private function resolveUserIdByEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        return User::query()
            ->where('email', $email)
            ->value('id');
    }
}
