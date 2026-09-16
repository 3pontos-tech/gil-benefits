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

    /*
    |--------------------------------------------------------------------------
    | Carteirinha impressa
    |--------------------------------------------------------------------------
    |
    | A arte é desenhada numa prancha de 1080 x 1350 px e impressa na proporção
    | 4:5; `width_mm` é a única medida física, e todo o resto deriva dela. Em
    | 120mm o menor texto da arte sai com 5,4pt, que ainda se lê em papel, e o
    | QR fica com 25mm, bem acima do mínimo que uma câmera de celular resolve.
    |
    | Frente e verso saem em páginas consecutivas, para impressão frente e
    | verso direta.
    |
    */
    'card' => [
        'width_mm' => (float) env('VOUCHER_CARD_WIDTH_MM', 120),
        'support_email' => env('VOUCHER_SUPPORT_EMAIL', 'ajuda@flammabeneficios.com'),
        'site' => env('VOUCHER_SITE', 'flammabeneficios.com'),
    ],

];
