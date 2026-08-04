<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Tipo de Atendimento',
        ],
        'cancel' => [
            'action_label' => 'Cancelar',
            'modal_heading' => 'Deseja cancelar sua consulta?',
            'modal_description' => 'Após a confirmação, a consulta será cancelada e não poderá ser restaurada automaticamente.',
            'notice_keeps_credit' => 'Você pode cancelar sua consulta até :hours horas antes do horário agendado. Em cancelamentos dentro desse prazo, seu crédito será restituído.',
            'notice_loses_credit' => 'Faltam menos de :hours horas para o horário agendado. Ao cancelar agora, seu crédito não será restituído.',
            'modal_submit_label' => 'Confirmar',
            'modal_cancel_label' => 'Cancelar',
            'confirmed' => [
                'heading' => 'Cancelamento confirmado!',
                'description' => 'Sua consulta foi cancelada com sucesso. Caso aplicável, o crédito será restituído conforme as regras de cancelamento.',
                'appointment_cancelled' => 'Consulta cancelada.',
                'credit_processing' => 'Crédito em processamento',
                'finish' => 'Finalizar',
            ],
        ],
        'schedule' => [
            'action_label' => 'Novo agendamento',
            'next' => 'Próximo',
            'category' => [
                'heading' => 'Vamos agendar sua consulta?',
                'description' => 'Escolha a categoria do seu agendamento.',
            ],
            'slot' => [
                'heading' => 'Escolha data e hora',
                'description' => 'Selecione uma data e horário para sua consulta entre as opções disponíveis.',
                'available_times' => 'Horários Disponíveis',
                'pick_date_first' => 'Selecione uma data para ver os horários disponíveis.',
                'no_slots' => 'Nenhum horário disponível para esta data.',
            ],
            'review' => [
                'heading' => 'Revisar e confirmar',
                'description' => 'Revise os dados de agendamento antes de confirmar sua consulta.',
                'category' => 'Categoria',
                'date' => 'Data',
                'time' => 'Horário',
                'duration' => 'Duração',
                'duration_value' => '60 minutos',
                'notice' => 'Você pode reagendar sua consulta até :hours horas antes do horário agendado.',
                'submit' => 'Agendar consultoria',
            ],
            'confirmed' => [
                'heading' => 'Agendamento confirmado!',
                'description' => 'Sua consulta foi agendada com sucesso. O seu agendamento já está confirmado.',
                'category_caption' => 'Categoria de consultoria',
                'awaiting_confirmation' => 'Aguardando confirmação',
                'back_home' => 'Voltar a página inicial',
            ],
        ],
        'reschedule' => [
            'action_label' => 'Reagendar',
            'modal_heading' => 'Reagendar Atendimento',
            'modal_description' => 'Escolha uma nova data e horário. Se o seu consultor não estiver disponível no horário escolhido, o agendamento voltará para a fila de atribuição.',
            'modal_submit_label' => 'Confirmar novo horário',
            'success' => 'Agendamento remarcado com sucesso.',
            'success_body_kept_consultant' => 'Seu consultor está disponível e foi mantido.',
            'success_body_unassigned' => 'Seu consultor não estava disponível neste horário. Vamos atribuir um novo consultor e avisaremos você em breve.',
            'calendar_sync_failed' => 'O agendamento foi remarcado, mas a agenda do consultor pode estar dessincronizada.',
            'next' => 'Próximo',
            'cannot_reschedule' => 'Este agendamento não pode mais ser reagendado.',
            'slot_unavailable' => 'O horário escolhido não está mais disponível. Selecione outro horário.',
            'failed' => 'Falha ao reagendar a consultoria.',
            'intro' => [
                'heading' => 'Vamos reagendar sua consulta?',
                'description' => 'Você pode reagendar sua consulta até :hours horas antes do horário agendado.',
                'keeps_current_slot' => 'Seu horário atual será mantido até a confirmação do novo agendamento',
            ],
            'slot' => [
                'heading' => 'Escolha nova data e hora',
                'description' => 'Selecione uma nova data e horário para sua consulta entre as opções disponíveis.',
            ],
            'review' => [
                'heading' => 'Resumo da alteração',
                'description' => 'Revise os dados do reagendamento antes de confirmar a mudança da sua consulta.',
                'category' => 'Categoria',
                'new_date' => 'Nova data',
                'new_time' => 'Novo horário',
                'duration' => 'Duração',
                'duration_value' => '60 minutos',
                'notice' => 'Seu horário atual será substituído após confirmação.',
                'submit' => 'Confirmar',
            ],
            'confirmed' => [
                'heading' => 'Reagendamento confirmado!',
                'description' => 'Sua consulta foi reagendada com sucesso. O novo agendamento já está confirmado.',
                'before' => 'Antes',
                'now' => 'Agora',
                'awaiting_confirmation' => 'Aguardando confirmação',
                'finish' => 'Finalizar',
            ],
        ],
        'records' => [
            'view' => [
                'label' => 'Ver Ata',
                'modal_heading' => 'Ata do atendimento',
                'close' => 'Fechar',
            ],
        ],
        'feedback' => [
            'action_label' => 'Avaliar',
            'modal_heading' => 'Avalie sua consultoria',
            'modal_description' => 'Sua avaliação nos ajuda a melhorar o serviço.',
            'rating' => 'Nota',
            'comment' => 'Comentário (opcional)',
            'submit' => 'Enviar avaliação',
            'submitted' => 'Avaliação enviada com sucesso!',
        ],
        'pages' => [
            'create' => [
                'cannot_book_now' => 'Não é possível agendar agora',
                'no_appointments_available' => 'Você não possui agendamentos disponíveis neste mês ou já possui uma consultoria em andamento. Finalize a anterior para agendar outra.',
                'book_appointment' => 'Agendar Consultoria',
                'booked_successfully' => 'Consultoria agendada com sucesso',
                'booking_failed' => 'Falha ao agendar consultoria',
                'back_to_list' => 'Voltar para a listagem',
            ],
        ],
    ],
    'credits' => [
        'navigation_label' => 'Meus Créditos',
        'title' => 'Meus Créditos',
        'columns' => [
            'status' => 'Status',
            'distributed_at' => 'Distribuído em',
            'purchased_at' => 'Comprado em',
        ],
    ],
    'documents' => [
        'tabs' => [
            'shared' => 'Compartilhados comigo',
            'mine' => 'Meus Documentos',
        ],
        'table' => [
            'title' => 'Nome do Documento',
            'extension_type' => 'Tipo',
            'active' => 'Ativo',
            'consultant' => 'Consultor',
            'created_at' => 'Data de Envio',
        ],
        'form' => [
            'heading' => 'Novo Documento',
            'title' => 'Nome do Documento',
            'active' => 'Ativo',
            'files' => 'Arquivo',
        ],
    ],
];
