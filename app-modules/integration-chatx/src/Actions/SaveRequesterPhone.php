<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Actions;

use App\Models\Users\User;
use TresPontosTech\IntegrationChatx\Support\ChatxPhone;

/**
 * Fills in the requester's phone from the WhatsApp number ChatX sends, when the account
 * has none, so the account is not left without a phone the person clearly uses.
 *
 * A phone already on the account is never replaced: it is what the person typed in
 * their profile, and the number on a chat is not proof they want it changed. Replies
 * go to the number on the ticket's origin anyway, so a different one costs nothing.
 */
final class SaveRequesterPhone
{
    public function execute(User $user, ?string $phone): void
    {
        $detail = $user->detail;

        if ($detail === null || filled($detail->phone_number)) {
            return;
        }

        $normalized = ChatxPhone::e164($phone);

        if ($normalized === null) {
            return;
        }

        $detail->update(['phone_number' => $normalized]);
    }
}
