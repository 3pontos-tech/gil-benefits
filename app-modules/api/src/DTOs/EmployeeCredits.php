<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use TresPontosTech\Credits\Models\UserCredit;

/**
 * Cota do ciclo e créditos avulsos do colaborador na empresa empregadora.
 */
final readonly class EmployeeCredits
{
    /**
     * @param  Collection<int, UserCredit>  $credits  do mais recente ao mais antigo, incluindo usados e expirados
     */
    public function __construct(
        public int $monthlyLimit,
        public int $monthlyLeft,
        public ?CarbonInterface $renewsAt,
        public Collection $credits,
    ) {}
}
