<?php

namespace TresPontosTech\Appointments\Enums;

enum CancellationActor: string
{
    case User = 'user';
    case Admin = 'admin';
    case System = 'system';
}
