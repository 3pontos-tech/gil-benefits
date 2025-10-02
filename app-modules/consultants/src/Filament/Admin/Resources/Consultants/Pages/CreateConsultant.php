<?php

namespace TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\Pages;

use Filament\Resources\Pages\CreateRecord;
use TresPontosTech\Consultants\Filament\Admin\Resources\Consultants\ConsultantResource;

class CreateConsultant extends CreateRecord
{
    protected static string $resource = ConsultantResource::class;
}
