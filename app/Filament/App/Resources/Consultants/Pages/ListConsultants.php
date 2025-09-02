<?php

namespace App\Filament\App\Resources\Consultants\Pages;

use App\Filament\App\Resources\Consultants\ConsultantResource;
use Filament\Resources\Pages\ListRecords;

class ListConsultants extends ListRecords
{
    protected static string $resource = ConsultantResource::class;

    protected string $view = 'filament.app.pages.list-consultants';
}
