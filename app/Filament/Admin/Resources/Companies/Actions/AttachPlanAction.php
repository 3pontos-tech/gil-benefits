<?php

namespace App\Filament\Admin\Resources\Companies\Actions;

use App\Action\Plans\ProcessPlanAction;
use App\DTO\ProcessPlanDTO;
use App\Models\Companies\Company;
use App\Models\Plans\Plan;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

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
                        Select::make('item_id')
                            ->label('Plan Item')
                            ->options(fn () => Plan::query()->with('items')->get()->mapWithKeys(function ($plan): array {
                                return [$plan->name => collect($plan->items()->pluck('type', 'id')->toArray())
                                    ->map(fn ($item) => $item->getLabel())];
                            })->toArray())
                            ->required(),
                        DatePicker::make('subscription_starting_at'),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                    ]),
            ])
            ->action(function (array $data, Company $record): void {
                app(ProcessPlanAction::class)->handle(ProcessPlanDTO::make($record->getKey(), $data));
            });
    }
}
