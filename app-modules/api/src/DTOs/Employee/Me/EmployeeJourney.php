<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Me;

use TresPontosTech\Appointments\DTO\UserJourney;

final readonly class EmployeeJourney
{
    /**
     * @param  list<HealthPoint>  $healthHistory  do mês mais antigo ao atual
     * @param  list<FocusItem>  $focus
     */
    public function __construct(
        public UserJourney $journey,
        public QuarterProgress $quarter,
        public array $healthHistory,
        public array $focus,
    ) {}
}
