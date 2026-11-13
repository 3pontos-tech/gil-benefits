<?php

declare(strict_types=1);

return [
    'greeting' => 'Olá, :name!',
    'greeting_anonymous' => 'Olá!',
    'created' => ':greeting Seu chamado foi criado com sucesso. Número do chamado: :protocol.',
    'in_progress' => ':greeting Seu chamado :protocol — :subject — foi recebido e já está em andamento com nosso time de suporte. Avisamos por aqui assim que houver novidade.',
    'reopened' => ':greeting Seu chamado :protocol — :subject — foi reaberto e voltou para a fila do nosso time. Assim que houver uma nova resposta, você recebe por aqui.',
    'resolved' => ':greeting Seu chamado :protocol — :subject — foi resolvido pelo nosso time. Se ainda estiver com dificuldade, é só responder esta mensagem que reabrimos o atendimento.',
    'closed' => ':greeting Seu chamado :protocol — :subject — foi encerrado. Se precisar de algo mais, é só abrir um novo atendimento por aqui.',
];
