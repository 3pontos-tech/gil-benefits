<?php

declare(strict_types=1);

namespace App\Enums;

use Basement\Webhooks\Contracts\InboundWebhookContract;

enum InboundWebhookSourceEnum: string implements InboundWebhookContract
{
    case Resend = 'resend';
    case Autentique = 'autentique';
    case Barte = 'barte';
}
