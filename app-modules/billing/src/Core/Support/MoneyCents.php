<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

use NumberFormatter;

/**
 * Dinheiro em centavos (FLM-41, decisão D-16).
 *
 * Toda conta do cockpit corre em `int` de centavos e só vira texto na borda de
 * exibição. O gateway já trabalha assim (`credit_orders.amount_cents`,
 * `billing_plan_prices.unit_amount_decimal`), e float em soma de receita acumula
 * erro que aparece justamente no total que vai para o sócio.
 */
final readonly class MoneyCents
{
    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Converte reais para centavos com o mesmo arredondamento que os adapters
     * usam no checkout (`round`, não `floor`), para o painel nunca divergir em
     * um centavo do que foi cobrado.
     */
    public static function fromReais(float $reais): self
    {
        return new self((int) round($reais * 100));
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function times(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function toReais(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency($this->toReais(), 'BRL');
    }
}
