<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TicketDestinationTypeEnum: string implements HasLabel
{
    case Monday = 'monday';
    case Email = 'email';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.destination_type.' . $this->value);
    }
}
