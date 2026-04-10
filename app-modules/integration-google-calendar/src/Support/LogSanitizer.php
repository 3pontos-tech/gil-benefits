<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Support;

class LogSanitizer
{
    private const int MAX_LENGTH = 500;

    private const string EMAIL_PATTERN = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

    private const string EMAIL_REPLACEMENT = '[email]';

    public static function sanitize(string $message): string
    {
        $scrubbed = preg_replace(self::EMAIL_PATTERN, self::EMAIL_REPLACEMENT, $message);

        if (mb_strlen($scrubbed) > self::MAX_LENGTH) {
            return mb_substr($scrubbed, 0, self::MAX_LENGTH) . '...[truncated]';
        }

        return $scrubbed;
    }
}
