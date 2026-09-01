<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Nível de risco de churn de uma empresa (STORY-235).
 *
 * A entrada na lista já exige as duas condições da story — utilização abaixo da
 * linha **e** valor acima da mediana. O nível gradua pela severidade da
 * subutilização, porque entre empresas que já pagam acima da mediana é o quanto
 * a base deixou de usar que separa "avisar o CS" de "ligar hoje".
 */
enum ChurnRiskLevel: string implements HasColor, HasLabel
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    /**
     * @param  float  $usageRate  Utilização da empresa, em pontos percentuais.
     */
    public static function fromUsageRate(float $usageRate): self
    {
        return match (true) {
            $usageRate < 20.0 => self::High,
            $usageRate < 30.0 => self::Medium,
            default => self::Low,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::High => 'danger',
            self::Medium => 'warning',
            self::Low => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::High => 'Alto',
            self::Medium => 'Médio',
            self::Low => 'Baixo',
        };
    }

    /**
     * Ordem de gravidade, para o ranking colocar o pior primeiro quando dois
     * clientes têm o mesmo valor em risco.
     */
    public function weight(): int
    {
        return match ($this) {
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}
