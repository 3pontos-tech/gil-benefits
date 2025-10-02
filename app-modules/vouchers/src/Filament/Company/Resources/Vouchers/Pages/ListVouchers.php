<?php

namespace TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Pages;

use App\Enums\VoucherStatusEnum;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\VoucherResource;
use TresPontosTech\Vouchers\Filament\Company\Resources\Vouchers\Widgets\UserVoucherStats;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            UserVoucherStats::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            Tab::make()
                ->label(__('Todos'))
                ->badgeColor('gray')
                ->icon('heroicon-o-inbox'),

            ...collect(VoucherStatusEnum::cases())->map(fn (VoucherStatusEnum $status): Tab => Tab::make()
                ->label($status->getLabel())
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn ($query) => $query->where('status', $status))
                ->badge(fn ($query) => filament()->getTenant()->vouchers()->where('status', $status)->count())
                ->icon($status->getIcon())
            )->toArray(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('request')
                ->record(filament()->getTenant())
                ->action(function ($record, array $data): void {
                    foreach (range(1, $data['quantity']) as $ignored) {
                        $record->vouchers()->create([
                            'code' => Uuid::uuid4()->toString(),
                            'status' => VoucherStatusEnum::Requested,
                            'valid_until' => now()->addDays(30),
                        ]);
                    }
                })
                ->schema([
                    TextEntry::make('company_name')
                        ->label('Company')
                        ->state(fn () => filament()->getTenant()->name),
                    TextInput::make('quantity')
                        ->required()
                        ->rules(['numeric', 'min:1', 'max:10', 'required'])
                        ->numeric(),
                ]),
        ];
    }
}
