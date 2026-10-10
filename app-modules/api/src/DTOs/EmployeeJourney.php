<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use TresPontosTech\PanelApp\DTOs\UserJourney;

/**
 * A jornada do painel mais o que só o app mostra: trimestre, histórico e foco.
 */
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
