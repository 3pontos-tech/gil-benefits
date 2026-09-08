<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Exceptions;

use RuntimeException;

class CannotGrantCreditException extends RuntimeException
{
    public static function invalidQuantity(): self
    {
        return new self('The quantity must be greater than zero.');
    }

    public static function emptyJustification(): self
    {
        return new self('The justification is required.');
    }
}
