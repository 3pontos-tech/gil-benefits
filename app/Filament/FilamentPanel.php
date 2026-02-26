<?php

namespace App\Filament;

enum FilamentPanel: string
{
    case User = 'app';

    case Admin = 'admin';

    case Company = 'company';

    case Consultant = 'consultant';

    case Guest = 'guest';
}
