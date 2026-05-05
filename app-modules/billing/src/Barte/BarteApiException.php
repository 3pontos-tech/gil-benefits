<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte;

use RuntimeException;

final class BarteApiException extends RuntimeException
{
    public function isUnauthorized(): bool
    {
        return $this->code === 401;
    }

    public function isNotFound(): bool
    {
        return $this->code === 404;
    }

    public function isUnprocessable(): bool
    {
        return $this->code === 422;
    }
}
