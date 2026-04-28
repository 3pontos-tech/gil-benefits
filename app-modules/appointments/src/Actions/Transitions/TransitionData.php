<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use TresPontosTech\Appointments\Enums\CancellationActor;

final readonly class TransitionData
{
    public function __construct(
        public ?CancellationActor $cancellationActor = null,
        public ?User $cancelledBy = null,
        public ?string $consultantId = null,
        public ?CarbonInterface $appointmentAt = null,
        public ?string $notes = null,
    ) {}
}
