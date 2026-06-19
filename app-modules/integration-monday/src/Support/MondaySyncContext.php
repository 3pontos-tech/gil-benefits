<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Support;

/**
 * Loop guard for the bidirectional sync. While a status change is being applied
 * from a Monday webhook, the outbound observer is muted so the same change is
 * not pushed straight back to the board.
 */
final class MondaySyncContext
{
    private static bool $muted = false;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function mute(callable $callback): mixed
    {
        $previous = self::$muted;
        self::$muted = true;

        try {
            return $callback();
        } finally {
            self::$muted = $previous;
        }
    }

    public static function isMuted(): bool
    {
        return self::$muted;
    }
}
