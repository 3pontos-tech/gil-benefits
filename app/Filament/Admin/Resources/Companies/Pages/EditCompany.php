<?php

namespace App\Filament\Admin\Resources\Companies\Pages;

use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Models\Companies\Company;
use App\Models\Plans\Plan;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Ramsey\Uuid\Uuid;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
        ];

        if (! $this->record->hasActivePlan()) {
            $actions[] = Action::make('attachPlan')
                ->schema([
                    Section::make('Choose Plan')
                        ->schema([
                            Select::make('plan_id')
                                ->label('Plan')
                                ->options(Plan::query()->pluck('name', 'id'))
                                ->required(),
                            DatePicker::make('renewal_date'),
                            Select::make('status')
                                ->options(['Active', 'Inactive']),
                        ]),
                ])
                ->action(function (array $data, Company $record): void {
                    $record->plans()->attach($record->id, $data);

                    $plan = Plan::query()->find($data['plan_id']);
                    $items = $plan->items;

                    foreach ($items as $item) {
                        $record->vouchers()->create([
                            'code' => Uuid::uuid4()->toString(),
                            'status' => 'pending',
                            'valid_until' => $data['renewal_date'],
                        ]);
                    }

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                });
        }

        // table belongs to many

        return $actions;
    }
}
