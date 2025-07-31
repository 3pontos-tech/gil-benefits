<?php

namespace App\Filament\Company\Resources\Users\Pages;

use App\Filament\Company\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
