<?php

namespace TresPontosTech\Appointments\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('appointments::resources.appointments.exceptions.slot_unavailable'));
    }
}
