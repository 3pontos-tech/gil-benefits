<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies\Actions;

use App\DTO\ProcessPlanDTO;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Plans\Actions\ProcessPlanAction;
use TresPontosTech\Plans\Models\Plan;

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
                        DatePicker::make('subscription_starting_at')
                            ->dehydrateStateUsing(fn ($state): string => Date::parse($state)),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive']),
                    ]),
            ])
            ->action(function (array $data, Company $record): void {
                app(ProcessPlanAction::class)->handle(ProcessPlanDTO::make($record->getKey(), $data));
            });
    }
}
