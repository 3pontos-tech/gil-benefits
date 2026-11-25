<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Support;

use Illuminate\Support\Facades\URL;
use TresPontosTech\Discounts\Models\DiscountGrant;

/**
 * A assinatura é relativa porque a API pode ser chamada por um host interno. O host sai sempre do `app.url`.
 */
final class DiscountInviteUrl
{
    public const ROUTE = 'discounts.invite';

    public const LEAVE_ROUTE = 'discounts.invite.leave';

    public const SESSION_KEY = 'discount_invite_grant_id';

    public const IGNORED_QUERY = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'clau_id'];

    public static function for(DiscountGrant $grant): string
    {
        return rtrim((string) config('app.url'), '/') . URL::temporarySignedRoute(
            self::ROUTE,
            $grant->expires_at,
            ['grant' => $grant->getKey()],
            absolute: false,
        );
    }
}
