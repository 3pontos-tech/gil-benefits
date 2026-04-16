<?php

declare(strict_types=1);

return [
    'appointments' => [
        'scheduled' => [
            'title' => 'Nova Consulta Agendada',
            'greeting' => 'Olá, **:name**!',
            'body' => 'Você tem uma nova consulta confirmada com **:name**.',
            'date_time' => 'Data e hora:',
            'meeting_link' => 'Link da reunião:',
            'meeting_link_label' => 'Acessar reunião',
            'employee_notes' => 'Observações do funcionário:',
            'panel_description' => 'Acesse o painel para visualizar todos os detalhes da consulta.',
            'button' => 'Acessar painel',
            'thanks' => 'Obrigado',
        ],
        'completed' => [
            'title' => 'Consulta Concluída',
            'greeting' => 'Olá, **:name**!',
            'body' => 'Sua consulta com **:consultant**, realizada em **:date**, foi concluída com sucesso.',
            'feedback' => 'Esperamos que a sessão tenha sido útil para você. Caso queira deixar um feedback sobre o atendimento, acesse o painel abaixo.',
            'button' => 'Avaliar consulta',
            'help' => 'Caso tenha alguma dúvida ou precise agendar uma nova consulta, estamos à disposição.',
            'thanks' => 'Obrigado',
        ],
        'cancelled' => [
            'title' => 'Consulta Cancelada',
            'greeting' => 'Olá, **:name**!',
            'body' => 'Informamos que a sua consulta com **:consultant**, que estava agendada para **:date**, foi cancelada.',
            'reschedule' => 'Se o cancelamento foi feito por engano ou se desejar reagendar, acesse o painel e crie uma nova solicitação.',
            'button' => 'Acessar painel',
            'support' => 'Se tiver dúvidas sobre o cancelamento, entre em contato com o suporte.',
            'thanks' => 'Obrigado',
        ],
    ],
    'users' => [
        'welcome' => [
            'title' => 'Bem-vindo ao :app!',
            'greeting' => 'Olá, **:name**!',
            'intro' => 'Estamos felizes em tê-lo(a) conosco. Sua conta foi criada com sucesso e você já pode acessar a plataforma de benefícios da sua empresa.',
            'features_title' => 'O que você pode fazer',
            'feature_appointments' => 'Agendar consultas com consultores especializados',
            'feature_documents' => 'Visualizar documentos compartilhados por sua equipe',
            'feature_history' => 'Acompanhar o histórico de seus atendimentos',
            'admin_created' => 'Seu acesso foi criado pelo administrador da sua empresa. Use as credenciais abaixo para entrar pela primeira vez:',
            'email_label' => '**E-mail:** :email',
            'temp_password' => '**Senha temporária:** :password',
            'security_note' => 'Por segurança, recomendamos que você altere sua senha após o primeiro acesso.',
            'login_prompt' => 'Para começar, acesse a plataforma com suas credenciais de login.',
            'button' => 'Acessar plataforma',
            'help' => 'Se tiver qualquer dúvida, não hesite em entrar em contato.',
            'thanks' => 'Obrigado',
        ],
    ],
];
