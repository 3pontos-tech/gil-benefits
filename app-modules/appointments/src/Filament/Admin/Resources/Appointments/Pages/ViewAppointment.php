<?php

namespace TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ViewRecord;
use TresPontosTech\Appointments\Filament\Admin\Resources\Appointments\AppointmentResource;
use TresPontosTech\IntegrationHighlevel\Actions\FetchConsultants;
use TresPontosTech\IntegrationHighlevel\Actions\FetchOpportunityPipelines;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('state')
                ->label('Change State')
                ->color('success')
                ->record($this->getParentRecord())
                ->schema([
                    Select::make('status')
                        ->optionsLimit(100)
                        ->native(false)
                        ->options(fn () => app(FetchOpportunityPipelines::class)->populateAction()),
                    Select::make('consultant_selected')
                        ->searchable()
                        ->native(false)
                        ->options(fn () => app(FetchConsultants::class)->populateAction()),
                ])->action(function (array $data): void {
                    dd($data);
                }),
            EditAction::make(),
        ];
    }
}
