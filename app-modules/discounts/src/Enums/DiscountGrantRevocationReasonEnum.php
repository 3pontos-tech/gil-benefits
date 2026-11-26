<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountGrantRevocationReasonEnum: string implements HasLabel
{
    case Manual = 'manual';
    case TooManyAttempts = 'too_many_attempts';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Revogado pela equipe',
            self::TooManyAttempts => 'Muitas tentativas de CPF',
        };
    }
}
