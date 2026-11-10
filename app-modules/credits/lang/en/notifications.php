<?php

declare(strict_types=1);

return [
    'credit_order_created' => [
        'title' => 'Credit order in progress',
        'body' => 'Your order of :quantity credit(s) is waiting for payment confirmation.',
    ],
    'credits_delivered' => [
        'title' => 'Credits arrived!',
        'body' => ':quantity credit(s) have been added to your account.',
    ],
    // Sem a quantidade: o push aparece na tela de bloqueio (#349).
    'credits_expiring' => [
        'title' => 'Your credit expires soon',
        'body' => 'Book an appointment by :date to use your credit before it expires.',
    ],
];
