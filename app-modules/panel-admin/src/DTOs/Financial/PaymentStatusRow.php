<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Uma linha do resumo de pagamentos do mês (STORY-236).
 *
 * As duas origens viajam separadas — assinaturas e compras de crédito — porque
 * são naturezas diferentes de cobrança e o financeiro precisa saber o que está
 * somando. O total é a soma das duas.
 */
final readonly class PaymentStatusRow
{
    public function __construct(
        public string $status,
        public string $label,
        public string $color,
        public int $subscriptions,
        public int $creditOrders,
        public int $totalCents,
    ) {}

    public function quantity(): int
    {
        return $this->subscriptions + $this->creditOrders;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'label' => $this->label,
            'color' => $this->color,
            'subscriptions' => $this->subscriptions,
            'credit_orders' => $this->creditOrders,
            'quantity' => $this->quantity(),
            'total_cents' => $this->totalCents,
        ];
    }
}
