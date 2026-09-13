<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | QR code das carteirinhas
    |--------------------------------------------------------------------------
    |
    | O QR é impresso num quadrado de 19mm, lido por celular, em papel que vai
    | ser dobrado e carregado no bolso. `ecc_level` M recupera cerca de 15% do
    | código danificado; L é frágil demais para esse uso e Q/H encolhem cada
    | módulo abaixo do que a câmera resolve nesse tamanho.
    |
    | `quietzone_size` é a margem branca em módulos, e 4 é o mínimo da norma:
    | zerar é a causa mais comum de "não lê".
    |
    */
    'qr_code' => [
        'ecc_level' => env('VOUCHER_QR_ECC_LEVEL', 'M'),
        'scale' => (int) env('VOUCHER_QR_SCALE', 6),
        'quietzone_size' => (int) env('VOUCHER_QR_QUIETZONE', 4),
    ],

];
