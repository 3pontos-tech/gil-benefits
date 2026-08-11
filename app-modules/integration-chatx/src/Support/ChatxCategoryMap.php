<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

/**
 * Translates ChatX's category text into our category enum.
 *
 * ChatX has no closed list of categories yet, so this has to cope with free text.
 * Three passes: our own enum values pass straight through, then the alias table in
 * config, then a fallback to `other`. Falling back is on purpose — a customer who
 * used a word we do not know should still get a ticket, and `other` routes to CS
 * where a human sorts it. The unknown value is logged so the alias table can grow
 * from what actually arrives.
 */
final class ChatxCategoryMap
{
    public static function toCategory(string $value): SupportTicketCategoryEnum
    {
        $normalised = self::normalise($value);

        $direct = SupportTicketCategoryEnum::tryFrom($normalised);

        if ($direct instanceof SupportTicketCategoryEnum) {
            return $direct;
        }

        /** @var array<string, string> $aliases */
        $aliases = config('chatx.category_map', []);

        $mapped = $aliases[$normalised] ?? null;

        if (is_string($mapped)) {
            $category = SupportTicketCategoryEnum::tryFrom($mapped);

            if ($category instanceof SupportTicketCategoryEnum) {
                return $category;
            }
        }

        Log::warning('Unmapped ChatX ticket category.', [
            'received' => $value,
            'normalised' => $normalised,
        ]);

        return SupportTicketCategoryEnum::Other;
    }

    /**
     * Lowercase, unaccented and trimmed, so "Financeiro", "financeiro" and
     * "Finanças" all reach the same key.
     */
    private static function normalise(string $value): string
    {
        return Str::of($value)->trim()->lower()->ascii()->value();
    }
}
