<?php

declare(strict_types=1);

return [
    'schedules' => [
        'title' => 'Agendas',

        'table' => [
            'columns' => [
                'name' => 'Nome',
                'type' => 'Tipo',
                'days' => 'Dias',
                'periods' => 'Períodos',
                'active' => 'Ativo',
            ],
        ],

        'actions' => [
            'add_availability' => 'Adicionar Disponibilidade',
            'add_blocked' => 'Adicionar Bloqueio',
        ],

        'form' => [
            'name' => 'Nome',
            'days_of_week' => 'Dias da Semana',
            'time_periods' => 'Períodos de Horário',
            'time_periods_blocked' => 'Períodos de Horário (deixe vazio para bloquear o dia inteiro)',
            'start' => 'Início',
            'end' => 'Fim',
            'start_date' => 'Data de Início',
            'end_date' => 'Data de Término',
            'placeholder_name_availability' => 'ex: Horário Comercial',
            'placeholder_name_blocked' => 'ex: Férias, Feriado',
            'placeholder_end_date' => 'Deixe vazio para um único dia',
        ],

        'days' => [
            'monday' => 'Segunda-feira',
            'tuesday' => 'Terça-feira',
            'wednesday' => 'Quarta-feira',
            'thursday' => 'Quinta-feira',
            'friday' => 'Sexta-feira',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ],
    ],

    'materials' => [
        'exceptions' => [
            'not_uploaded_by_you' => 'Só é possível remover os materiais que você enviou.',
        ],
    ],

    // Aviso e push do material compartilhado (#349): sem o título do material, que pode revelar
    // a situação financeira da pessoa na tela de bloqueio.
    'notifications' => [
        'material_shared' => [
            'title' => 'Novo material do seu consultor',
            'body' => 'Seu consultor compartilhou um material com você.',
        ],
        'push' => [
            'material_shared' => [
                'body' => 'Seu consultor compartilhou um material com você. Toque para abrir.',
            ],
        ],
    ],
];
