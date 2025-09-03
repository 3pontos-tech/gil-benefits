<?php

namespace App\Filament\Admin\Resources\Companies\Actions;

use App\Enums\VoucherStatusEnum;
use App\Models\Companies\Company;
use App\Models\Plans\Item;
use App\Models\Plans\Plan;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Number;
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
                        Select::make('item_id')
                            ->label('Plan Item')
                            ->options(fn() => Plan::query()->with('items')->get()->mapWithKeys(function ($plan) {
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

                $record->plans()->attach($data['item_id'], [
                    'status' => $data['status'],
                    'subscription_starting_at' => $data['subscription_starting_at'],
                ]);

                $item = Item::query()->find($data['item_id']);

                foreach (range(1, $item->plan->hours_included) as $item) {
                    $record->vouchers()->create([
                        'code' => Uuid::uuid4()->toString(),
                        'status' => VoucherStatusEnum::Pending,
                        'valid_until' => Carbon::parse($data['subscription_starting_at'])->addMonth(),
                    ]);
                }
            });
    }
}
