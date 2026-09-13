<?php

declare(strict_types=1);

return [
    'pdf' => [
        'ready_title' => 'PDF de vouchers pronto',
        'ready_body' => 'As carteirinhas do lote :batch estão prontas para impressão.',
        'download' => 'Baixar PDF',
        'failed_title' => 'Não foi possível gerar o PDF',
        'failed_body' => 'Tente novamente. Se persistir, avise o time técnico.',
    ],

    'errors' => [
        'code_not_found' => 'Código de voucher inválido.',
        'code_exhausted' => 'Este código já foi utilizado.',
        'already_redeemed' => 'Você já resgatou este código.',
        'batch_expired' => 'Este código está fora do prazo de resgate.',
        'program_inactive' => 'O programa desta empresa parceira não está mais vigente.',
        'not_from_user_company' => 'Este código não pertence à sua empresa.',
    ],
];
