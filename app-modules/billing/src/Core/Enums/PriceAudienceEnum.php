<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Para quem este preço é praticado.
 *
 * O subsídio da empresa não é modelado em lugar nenhum: ele está embutido no
 * valor do preço. Quem tem empregador paga o valor subsidiado; quem não tem
 * — os usuários do tenant default — paga o cheio.
 */
enum PriceAudienceEnum: string implements HasColor, HasLabel
{
    /** Colaborador de uma empresa que banca parte da mensalidade. */
    case Subsidized = 'subsidized';

    /** Usuário sem empregador por trás: paga o valor cheio. */
    case Standalone = 'standalone';

    public function getColor(): array
    {
        return match ($this) {
            self::Subsidized => Color::Green,
            self::Standalone => Color::Amber,
        };
    }

    public function getLabel(): string
    {
        return __('billing::enums.price_audience.' . $this->value);
    }
}
