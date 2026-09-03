<?php

declare(strict_types=1);

return [
    'appointment_status' => [
        'pending' => 'Pendente',
        'active' => 'Agendado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
        'cancelled_late' => 'Cancelado (fora do prazo)',
        'no_show' => 'Não compareceu',
    ],
    'appointment_history_action_type' => [
        'consultant_assigned' => 'Consultor atribuído',
        'consultant_left' => 'Consultor removido',
        'consultant_changed' => 'Consultor alterado',
        're_scheduled' => 'Reagendado',
        'no_show_marked' => 'Não comparecimento registrado',
    ],
    'appointment_history_actor' => [
        'admin' => 'Administrador',
        'user' => 'Cliente',
        'consultant' => 'Consultor',
    ],
];
