<?php

namespace App\Filament\Admin\Resources\Actions;

use App\Models\Companies\Company;
use App\Models\Plans\Plan;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Ramsey\Uuid\Uuid;

class AttachPlanAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attach-plan-action';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Attach Plan')
            ->schema([
                Section::make('Choose Plan')
                    ->schema([
                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::query()->pluck('name', 'id'))
                            ->required(),
                        DatePicker::make('renewal_date'),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                    ]),
            ])
            ->action(function (array $data, Company $record): void {

                $record->plans()->attach($record->id, $data);

                $plan = Plan::query()->find($data['plan_id']);

                foreach (range(1, $plan->hours_included) as $item) {
                    $record->vouchers()->create([
                        'code' => Uuid::uuid4()->toString(),
                        'status' => 'pending',
                        'valid_until' => $data['renewal_date'],
                    ]);
                }
            });
    }
}
