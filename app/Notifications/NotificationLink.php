<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * The call to action of a notice: where it takes the person and what the button says.
 */
final readonly class NotificationLink
{
    public function __construct(
        public string $label,
        public string $url,
    ) {}
}
