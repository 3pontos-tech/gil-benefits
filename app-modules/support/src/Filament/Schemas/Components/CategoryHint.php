<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Filament\Schemas\Components;

use Filament\Facades\Filament;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

/**
 * Reactive tips box rendered under the category select. The copy is hardcoded per
 * category in SupportTicketCategoryEnum::getHint(); for login/access issues it also
 * links to the profile page when the form runs inside an authenticated panel (guests
 * have no profile, so only the password-reset guidance shows).
 *
 * The bound `category` field must be ->live() so the box reacts to selection.
 */
class CategoryHint
{
    public static function make(string $field = 'category'): Text
    {
        return Text::make(fn (Get $get): ?HtmlString => self::content($get($field)))
            ->visible(fn (Get $get): bool => self::content($get($field)) instanceof HtmlString)
            ->icon(Heroicon::OutlinedInformationCircle)
            ->color('info');
    }

    private static function content(mixed $value): ?HtmlString
    {
        // The select uses ->options(enum::class), so the state is an enum instance;
        // it may still arrive as the backing string (e.g. on first hydration).
        $category = match (true) {
            $value instanceof SupportTicketCategoryEnum => $value,
            is_string($value) => SupportTicketCategoryEnum::tryFrom($value),
            default => null,
        };

        if ($category === null) {
            return null;
        }

        $lines = array_map(static fn (string $line): string => e($line), $category->getHint());

        if ($lines === []) {
            return null;
        }

        // Logged-in users can change the password themselves; surface the profile link
        // only when a panel context exists (getProfileUrl() is null for guests).
        if ($category === SupportTicketCategoryEnum::LoginAccess) {
            $profileUrl = Filament::getProfileUrl();

            if ($profileUrl !== null) {
                $lines[] = sprintf(
                    '<a href="%s" class="fi-link font-medium underline">%s</a>',
                    e($profileUrl),
                    e(__('support::enums.category_hint.login_access_profile_link')),
                );
            }
        }

        $html = implode('', array_map(
            static fn (string $line): string => '<span class="block">' . $line . '</span>',
            $lines,
        ));

        return new HtmlString($html);
    }
}
