<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Status de uma empresa na visão do financeiro (FLM-41, STORY-233).
 *
 * Unifica duas fontes que hoje vivem separadas: `stripe_status` da assinatura
 * (escrito pelos handlers de Virtu e Barte) e `company_plans.status` do contrato.
 * Nenhuma tela consegue responder "quantas empresas estão inadimplentes?" sem
 * essa unificação, porque a resposta depende de qual das duas a empresa usa.
 */
enum CompanyFinancialStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Trial = 'trial';
    case Delinquent = 'delinquent';
    case Cancelled = 'cancelled';

    /** Empresa sem assinatura e sem contrato: existe no cadastro e não paga nada. */
    case None = 'none';

    public function getColor(): array
    {
        return match ($this) {
            self::Active => Color::Emerald,
            self::Trial => Color::Blue,
            self::Delinquent => Color::Amber,
            self::Cancelled => Color::Red,
            self::None => Color::Gray,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::Trial => 'heroicon-o-beaker',
            self::Delinquent => 'heroicon-o-exclamation-triangle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::None => 'heroicon-o-minus-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Trial => 'Em Trial',
            self::Delinquent => 'Inadimplente',
            self::Cancelled => 'Cancelada',
            self::None => 'Sem plano',
        };
    }

    /**
     * Status que representam uma relação comercial viva — os que contam como
     * base de clientes, mesmo quando o pagamento está atrasado.
     *
     * @return list<self>
     */
    public static function livingCases(): array
    {
        return [self::Active, self::Trial, self::Delinquent];
    }
}
