<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use TresPontosTech\App\Filament\Widgets\UserCreditStatsWidget;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\PanelCompany\Filament\Actions\PurchaseCreditsAction;

class UserCreditsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'my-credits';

    protected string $view = 'user-credits';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::CreditCard;

    public static function getNavigationLabel(): string
    {
        return __('panel-app::resources.credits.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel-app::resources.credits.title');
    }

    protected function getHeaderWidgets(): array
    {
        return [UserCreditStatsWidget::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserCredit::query()
                    ->where('holder_id', auth()->id())
                    ->where('company_id', filament()->getTenant()?->getKey())
                    ->latest()
            )
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('panel-app::resources.credits.columns.status'))
                    ->formatStateUsing(fn (UserCreditStatusEnum $state): string => $state->getLabel())
                    ->color(fn (UserCreditStatusEnum $state): array => $state->getColor()),
                TextColumn::make('transferred_at')
                    ->dateTime('d/m/Y H:i')
                    ->label(__('panel-app::resources.credits.columns.distributed_at'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label(__('panel-app::resources.credits.columns.purchased_at')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-app::resources.credits.columns.status'))
                    ->options(UserCreditStatusEnum::class),
            ])
            ->headerActions([
                PurchaseCreditsAction::make()
                    ->forBillable(auth()->user(), static::getUrl(), static::getUrl()),
            ]);
    }
}
