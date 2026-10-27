<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Enums\CancellationActor;

final readonly class TransitionData
{
    /**
     * @param  CarbonInterface|null  $cancelledAt  Instante em que o cancelamento é avaliado. Quem já
     *                                             conferiu o prazo passa o mesmo instante, para a transição
     *                                             não reler o relógio; sem ele, vale `now()`.
     */
    public function __construct(
        public ?CancellationActor $cancellationActor = null,
        public ?User $cancelledBy = null,
        public ?string $consultantId = null,
        public ?CarbonInterface $appointmentAt = null,
        public ?string $notes = null,
        public ?User $noShowMarkedBy = null,
        public ?CarbonInterface $cancelledAt = null,
    ) {}
}
