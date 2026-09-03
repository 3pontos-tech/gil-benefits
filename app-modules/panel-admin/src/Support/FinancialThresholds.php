<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Support;

/**
 * Linhas de corte do cockpit financeiro, como escritas nas stories.
 *
 * Ficam aqui, e não espalhadas nas Actions, porque são números de negócio: o
 * DoD da STORY-235 exige revisão da régua de churn com o CS, e a revisão precisa
 * ter um lugar só para acontecer.
 */
final class FinancialThresholds
{
    /**
     * Utilização abaixo desta linha coloca a empresa na lista de risco de churn,
     * desde que o valor mensal também esteja acima da mediana (STORY-235).
     */
    public const float CHURN_USAGE_RATE = 40.0;

    /**
     * Acima desta utilização a empresa é considerada saudável — é o número que
     * o cenário de estado vazio da STORY-235 usa.
     */
    public const float HEALTHY_USAGE_RATE = 60.0;

    /**
     * Acima desta fatia da receita B2B, uma única empresa dispara o alerta de
     * alta concentração (STORY-232).
     */
    public const float REVENUE_CONCENTRATION = 30.0;
}
