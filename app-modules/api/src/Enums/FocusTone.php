<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums;

/**
 * Tom do item de foco na folha de saúde financeira do app (cor e ícone).
 */
enum FocusTone: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Info = 'info';
}
