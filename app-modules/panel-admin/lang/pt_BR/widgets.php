<?php

declare(strict_types=1);

return [
    'stats_overview' => [
        'active_plans' => 'Planos Ativos',
        'active_plans_description' => 'Planos ativos no momento',
        'new_users' => 'Novos Usuários',
        'new_users_description' => 'Esta semana',
        'total_companies' => 'Total de Empresas',
        'total_appointments' => 'Total de Consultas',
        'overall' => 'Geral',
    ],
    'latest_companies' => [
        'heading' => 'Últimas Empresas',
        'plan' => 'Plano',
    ],
    'metrics' => [
        'kpis_overview' => [
            'conclusion_rate' => 'Taxa de Conclusão',
            'conclusion_rate_description' => ':completed de :total consultas concluídas',
            'cancellation_rate' => 'Taxa de Cancelamento',
            'cancellation_rate_description' => ':cancelled de :total consultas canceladas',
            'pending' => 'Consultas Pendentes',
            'pending_description' => 'Aguardando atendimento',
            'avg_per_consultant' => 'Média por Consultor',
            'avg_per_consultant_description' => 'Agendamentos ativos por consultor',
            'last_seven_days' => 'Agendamentos no Período',
            'last_seven_days_description' => 'Criados no período selecionado',
        ],
        'appointment_volume' => [
            'heading' => 'Volume de Atendimentos',
            'filter_today' => 'Hoje',
            'filter_week' => 'Esta Semana',
            'filter_month' => 'Este Mês',
            'dataset_total' => 'Total',
            'dataset_completed' => 'Concluídos',
        ],
        'appointments_by_status' => [
            'heading' => 'Distribuição por Status',
        ],
        'appointments_by_category' => [
            'heading' => 'Distribuição por Categoria',
        ],
        'consultants_ranking' => [
            'heading' => 'Ranking de Consultores',
            'column_consultant' => 'Consultor',
            'column_total' => 'Total',
            'column_completed' => 'Concluídos',
            'column_pending' => 'Pendentes',
            'column_completion_rate' => 'Taxa de Conclusão',
        ],
        'companies_ranking' => [
            'heading' => 'Ranking de Empresas',
            'column_company' => 'Empresa',
            'column_total' => 'Total',
            'column_completed' => 'Concluídos',
            'column_pending' => 'Pendentes',
            'column_completion_rate' => 'Taxa de Conclusão',
        ],
        'rankings' => [
            'heading' => 'Rankings',
            'tab_consultants' => 'Consultores',
            'tab_companies' => 'Empresas',
            'column_consultant' => 'Consultor',
            'column_company' => 'Empresa',
            'column_total' => 'Total',
            'column_completed' => 'Concluídos',
            'column_pending' => 'Pendentes',
            'column_completion_rate' => 'Taxa de Conclusão',
        ],
    ],
    'quick_actions' => [
        'create_user' => 'Criar Usuário',
        'create_user_description' => 'Adicionar novo usuário ao sistema',
        'create_company' => 'Criar Empresa',
        'create_company_description' => 'Cadastrar nova empresa na plataforma',
        'manage_users' => 'Gerenciar Usuários',
        'manage_users_description' => 'Visualizar e editar usuários existentes',
    ],
];
