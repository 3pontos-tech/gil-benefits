<?php

declare(strict_types=1);

return [
    'password_reset_code' => [
        'subject' => 'Seu código para redefinir a senha do :app',
        'title' => 'Redefinir a senha',
        'intro' => 'Digite este código no app :app. Ele vale por :minutes minutos.',
        'ignore' => 'Se não foi você, ignore este e-mail: a sua senha continua a mesma.',
    ],
];
