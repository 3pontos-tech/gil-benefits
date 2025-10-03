<?php

namespace App\Filament;

enum FilamentPanel: string
{
    case User = 'user';
    case Admin = 'admin';
    case Company = 'company';
    case Consultant = 'consultant';
}
