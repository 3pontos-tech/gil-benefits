<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Support;

/**
 * Brazilian phone in the shape ChatX uses for WhatsApp: country code, area code and
 * number, digits only (`5511999999999`).
 *
 * Accounts store phones in mixed shapes (E.164 from the profile, masked or bare digits
 * from imports and older rows), so every phone crossing to or from ChatX goes through
 * here. Anything that is not a Brazilian landline or mobile comes back null rather than
 * being sent or saved wrong.
 */
final class ChatxPhone
{
    /**
     * A number written with `+` or `00` already names its country, so only 55 is accepted
     * then. Without that check `+1 (202) 555-0123` would lose the `+`, look like a
     * Brazilian number with area code 12 and be saved as `+5512025550123`.
     */
    public static function digits(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($phone, '+') || str_starts_with($phone, '00')) {
            $digits = str_starts_with($phone, '00') ? substr($digits, 2) : $digits;

            return self::isBrazilianWithCountryCode($digits) ? $digits : null;
        }

        if (in_array(strlen($digits), [10, 11], true)) {
            return '55' . $digits;
        }

        return self::isBrazilianWithCountryCode($digits) ? $digits : null;
    }

    private static function isBrazilianWithCountryCode(string $digits): bool
    {
        return in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55');
    }

    /**
     * Same number with a leading `+`, the format the profile's phone input stores, so a
     * phone saved from ChatX looks like any other phone in the database.
     */
    public static function e164(?string $phone): ?string
    {
        $digits = self::digits($phone);

        return $digits === null ? null : '+' . $digits;
    }
}
