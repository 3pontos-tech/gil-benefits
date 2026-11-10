<?php

declare(strict_types=1);

namespace App\Notifications;

use Carbon\CarbonInterface;

/**
 * O que o aviso diz no celular quando também vira push. O texto é próprio, porque aparece na
 * tela de bloqueio: nunca leva o nome do consultor, o assunto, valores, título de material nem
 * o link da sala. Só ganha push o aviso que a pessoa precisa receber na hora, porque saber
 * depois a prejudica (gil-benefits#349).
 */
final readonly class NotificationPush
{
    /**
     * @param  bool  $urgent  entrega imediata mesmo com o celular em repouso (prioridade alta)
     * @param  CarbonInterface|null  $expiresAt  depois disso o aviso perde o sentido e não é entregue
     * @param  array<string, string>  $data  campos do payload além do tipo e dos ids
     */
    public function __construct(
        public string $title,
        public string $body,
        public bool $urgent = false,
        public ?CarbonInterface $expiresAt = null,
        public array $data = [],
    ) {}
}
