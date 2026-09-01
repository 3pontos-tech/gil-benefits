<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Valor de uma consultoria
    |--------------------------------------------------------------------------
    |
    | Quanto vale uma consultoria que consumiu crédito do cliente (STORY-239).
    | É um número único: a plataforma não vende consultoria avulsa, então não
    | existe preço por consultoria em lugar nenhum do domínio — só o volume, que
    | ela conhece, multiplicado por um valor que a Flamma declara.
    |
    | Fica no arquivo, e não numa tela, porque entra em relatório financeiro:
    | mudar exige PR, o que deixa rastro de quando e por quem mudou. Alterar o
    | valor recalcula os meses passados — o valor das consultorias é sempre
    | computado com o número vigente, e a tela diz isso.
    |
    | `null` significa "não configurado": o valor não é calculado e a tela avisa,
    | em vez de exibir zero.
    |
    */
    'consulting_value_in_cents' => env('FLAMMA_CONSULTING_VALUE_IN_CENTS') === null
        ? null
        : (int) env('FLAMMA_CONSULTING_VALUE_IN_CENTS'),

];
