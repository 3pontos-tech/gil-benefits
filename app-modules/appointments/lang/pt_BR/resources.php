<?php

return [
    'appointments' => [
        'label' => 'Agendamento',
        'plural' => 'Agendamentos',
        'navigation' => 'Agendamentos',

        'table' => [
            'columns' => [
                'consultant' => 'Consultor',
                'user' => 'Usuário',
                'appointment_at' => 'Data e Hora',
                'status' => 'Status',
                'created_at' => 'Criado em',
                'updated_at' => 'Atualizado em',
            ],
            'actions' => [
                'view' => 'Ver',
                'edit' => 'Editar',
                'delete_selected' => 'Excluir selecionados',
            ],
        ],

        'wizard' => [
            'steps' => [
                'consultant' => 'Consultor',
                'pick_datetime' => 'Escolher Data e Hora',
                'review_confirm' => 'Revisar e Confirmar',
            ],
            'labels' => [
                'choose_consultant' => 'Escolha seu consultor',
                'date' => 'Data',
                'available_times' => 'Horários Disponíveis',
                'duration' => 'Duração',
                'duration_default' => '60 minutos',
                'summary' => 'Resumo',
                'notes' => 'Observações',
            ],
            'actions' => [
                'submit' => 'Iniciar pesquisa',
            ],
        ],
    ],
];
