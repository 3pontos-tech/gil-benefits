<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Custo de repasse por consultoria
    |--------------------------------------------------------------------------
    |
    | Quanto a Flamma paga ao parceiro por consultoria que consumiu crédito
    | (STORY-239). Vale para todo consultor que não tenha um valor próprio em
    | `consultants.cost_per_appointment_cents`.
    |
    | Fica no arquivo, e não numa tela, porque é um número que entra em relatório
    | de repasse: mudar exige PR, o que deixa rastro de quando e por quem mudou.
    | Alterar o valor recalcula os meses passados — o repasse é sempre computado
    | com o custo vigente, e a tela diz isso.
    |
    | `null` significa "não configurado": o repasse não é calculado e a tela avisa,
    | em vez de exibir zero.
    |
    */
    'consulting_cost_in_cents' => env('FLAMMA_CONSULTING_COST_IN_CENTS') === null
        ? null
        : (int) env('FLAMMA_CONSULTING_COST_IN_CENTS'),

];
