<?php

declare(strict_types=1);

use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

it('scrubs email addresses from messages', function (): void {
    $message = 'Consultant consultant@example.com is not in the Google Workspace domain';

    $sanitized = LogSanitizer::sanitize($message);

    expect($sanitized)->toBe('Consultant [email] is not in the Google Workspace domain');
});

it('scrubs multiple email addresses in the same message', function (): void {
    $message = 'Failed request from user@foo.com to admin@bar.co.uk';

    $sanitized = LogSanitizer::sanitize($message);

    expect($sanitized)->toBe('Failed request from [email] to [email]');
});

it('truncates messages longer than the max length', function (): void {
    $message = str_repeat('a', 600);

    $sanitized = LogSanitizer::sanitize($message);

    expect($sanitized)
        ->toEndWith('...[truncated]')
        ->and(mb_strlen($sanitized))->toBe(500 + mb_strlen('...[truncated]'));
});

it('preserves short messages without emails unchanged', function (): void {
    $message = 'Failed to get access token: 403 Forbidden';

    $sanitized = LogSanitizer::sanitize($message);

    expect($sanitized)->toBe($message);
});
