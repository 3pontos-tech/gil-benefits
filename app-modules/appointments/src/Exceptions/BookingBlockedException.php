<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;

class BookingBlockedException extends RuntimeException
{
    /**
     * @param  list<string>  $reasons  Motivos nas palavras do colaborador (BookingBlockReasons).
     */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct(implode(' ', $reasons));
    }

    /**
     * @param  list<string>  $reasons
     */
    public static function because(array $reasons): self
    {
        return new self($reasons);
    }
}
