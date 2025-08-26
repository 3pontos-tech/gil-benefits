<?php

namespace App\Filament\App\Resources\Consultants\Pages;

use App\Filament\App\Resources\Consultants\ConsultantResource;
use App\Models\Consultant;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ViewConsultant extends ViewRecord
{
    protected static string $resource = ConsultantResource::class;

    public function mount($record = null): void
    {
        $this->record = Consultant::query()->whereSlug($record)->firstOrFail();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)
                ->columnSpanFull()
                ->schema([View::make('filament.shared.consultants.profile')])]);
    }
}
