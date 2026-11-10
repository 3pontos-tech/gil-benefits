<?php

declare(strict_types=1);

return [
    'credit_order_created' => [
        'title' => 'Pedido de créditos em andamento',
        'body' => 'Seu pedido de :quantity crédito(s) está aguardando confirmação do pagamento.',
    ],
    'credits_delivered' => [
        'title' => 'Créditos chegaram!',
        'body' => ':quantity crédito(s) foram adicionados à sua conta.',
    ],
    // Sem a quantidade: o push aparece na tela de bloqueio (#349).
    'credits_expiring' => [
        'title' => 'Seu crédito vence em breve',
        'body' => 'Agende um encontro até :date para usar seu crédito antes do vencimento.',
    ],
];
