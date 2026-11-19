<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Actions;

use App\Models\Users\User;
use TresPontosTech\IntegrationChatx\DTO\ChatxTicketDTO;
use TresPontosTech\IntegrationChatx\Exceptions\UnknownRequesterException;

/**
 * Matches the person ChatX describes to an account, or refuses the ticket.
 *
 * This deliberately does not go through CreateSupportTicketAction's own e-mail
 * lookup. That one is for the public help centre, where an unrecognised e-mail is
 * fine — the ticket is simply filed as an anonymous visitor. Tickets arriving from
 * an integration get the opposite treatment: no account, no ticket. Otherwise
 * anyone able to reach the endpoint could open tickets under invented identities.
 */
final class ResolveChatxRequester
{
    public function execute(ChatxTicketDTO $dto): User
    {
        $user = User::query()
            ->where('email', $dto->visitorEmail)
            ->first();

        throw_unless($user instanceof User, UnknownRequesterException::class);

        throw_if($dto->visitorDocument !== null && ! $this->documentBelongsTo($user, $dto->visitorDocument), UnknownRequesterException::class);

        return $user;
    }

    /**
     * CPF lives on `user_details.tax_id` (`document_id` on that table is the RG).
     *
     * Stored values are inconsistent — some rows were imported with the mask, some
     * without — so both spellings are compared rather than assuming one of them.
     */
    private function documentBelongsTo(User $user, string $document): bool
    {
        $digits = preg_replace('/\D/', '', $document) ?? '';

        if (strlen($digits) !== 11) {
            return false;
        }

        $taxId = $user->detail?->tax_id;

        if ($taxId === null) {
            return false;
        }

        return (preg_replace('/\D/', '', $taxId) ?? '') === $digits;
    }
}
