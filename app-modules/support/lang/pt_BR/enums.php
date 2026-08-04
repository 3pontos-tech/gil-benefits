<?php

declare(strict_types=1);

return [
    'category' => [
        'login_access' => 'Login / Acesso',
        'platform_error' => 'Erro na plataforma',
        'bug' => 'Bug confirmado',
        'integration' => 'Integração',
        'performance' => 'Lentidão / Performance',
        'scheduling_issue' => 'Agendamento',
        'financial_issue' => 'Financeiro / NF / Reembolso',
        'contract_plan' => 'Contrato / Plano / Upgrade',
        'cancellation_complaint' => 'Cancelamento / Reclamação',
        'suggestion_feedback' => 'Sugestão / Feedback',
        'general_question' => 'Dúvida operacional',
        'other' => 'Outros',
    ],
    'category_hint' => [
        'login_access' => [
            'password' => 'Para problemas de login ou acesso, você mesmo pode redefinir sua senha pelo link "Esqueci minha senha" na tela de login, sem abrir um chamado.',
            'plan' => 'Se a senha estiver correta e ainda assim não conseguir acessar, verifique se a sua empresa não cancelou o plano.',
        ],
        'login_access_profile_link' => 'Alterar minha senha no perfil',
        'scheduling_issue' => 'Para reagendar, cancele o agendamento atual e crie um novo na data desejada. Cancelamentos feitos com menos de :hours horas de antecedência consomem o agendamento mensal ou o crédito utilizado, sem devolução. Com :hours horas ou mais de antecedência, nada é descontado.',
    ],
    'ticket_status' => [
        'pending' => 'Pendente',
        'in_progress' => 'Em andamento',
        'resolved' => 'Resolvido',
        'closed' => 'Encerrado',
    ],
    'destination_channel' => [
        'support_ti' => 'Time de TI',
        'financial' => 'Financeiro',
        'commercial' => 'Comercial',
        'cs' => 'Dúvidas e Reclamações',
        'product' => 'Produto',
    ],
    'destination_type' => [
        'monday' => 'Monday',
        'email' => 'E-mail',
    ],
    'destination_status' => [
        'pending' => 'Pendente',
        'sent' => 'Enviado',
        'failed' => 'Falhou',
    ],
];
