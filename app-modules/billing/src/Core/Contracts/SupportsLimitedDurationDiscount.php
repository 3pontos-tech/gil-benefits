<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

/**
 * O gateway que implementa esta interface cobra `CheckoutDiscount::$durationCycles`, ou seja,
 * aplica o desconto só nas N primeiras recorrências. Sem ela, o desconto com duração limitada
 * é recusado no checkout e não deve ser ofertado a ninguém.
 */
interface SupportsLimitedDurationDiscount {}
