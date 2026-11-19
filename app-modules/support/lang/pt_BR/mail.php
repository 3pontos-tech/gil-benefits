<?php

declare(strict_types=1);

return [
    'confirmation' => [
        'subject' => 'Solicitação recebida — :protocol',
    ],
    'status_updated' => [
        'subjects' => [
            'in_progress' => 'Seu chamado está em andamento — :protocol',
            'resolved' => 'Seu chamado foi resolvido — :protocol',
            'closed' => 'Seu chamado foi encerrado — :protocol',
        ],
        'intros' => [
            'in_progress' => 'Estamos cuidando do seu chamado! Ele está **em andamento** e nossa equipe já está trabalhando na solução.',
            'resolved' => 'Boas notícias! Seu chamado foi marcado como **resolvido**.',
            'closed' => 'Seu chamado foi **encerrado**.',
        ],
    ],
    'reopened' => [
        'subject' => 'Chamado reaberto — [:protocol] :subject',
        'preheader' => 'O chamado :protocol foi reaberto',
        'heading' => 'Chamado reaberto',
        'labels' => [
            'category' => 'Categoria',
            'requester' => 'Solicitante',
            'reopened_by' => 'Reaberto por',
            'reason' => 'Motivo da reabertura:',
            'subject' => 'Assunto:',
        ],
        'opened_at' => 'Aberto em :date',
    ],
];
