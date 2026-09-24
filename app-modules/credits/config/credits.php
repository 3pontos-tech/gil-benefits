<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Preço de um crédito
    |--------------------------------------------------------------------------
    |
    | Quanto custa um crédito avulso, em centavos. É o valor cobrado na compra
    | fora do plano e o registrado em `credit_orders.amount_cents`.
    |
    */
    'price_in_cents' => (int) env('CREDIT_PRICE_IN_CENTS', 15_000),

];
