<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\PanelApp\Filament\Widgets\UserCreditStatsWidget;
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
                    ->with('appointment')
                    ->latest()
            )
            ->heading(__('panel-app::resources.credits.history.heading'))
            ->description(__('panel-app::resources.credits.history.description'))
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('panel-app::resources.credits.columns.status'))
                    ->icon(fn (UserCreditStatusEnum $state): Heroicon => match ($state) {
                        UserCreditStatusEnum::Available => Heroicon::Check,
                        UserCreditStatusEnum::InUse, UserCreditStatusEnum::Used => Heroicon::ArrowPath,
                        UserCreditStatusEnum::Expired => Heroicon::XMark,
                    })
                    ->extraCellAttributes(fn (UserCredit $record): array => [
                        'class' => 'fi-apt-credit-' . str_replace('_', '-', $record->status->value),
                        'data-apt-label' => __('panel-app::resources.credits.columns.status'),
                    ]),
                TextColumn::make('appointment.category_type')
                    ->label(__('panel-app::resources.credits.columns.distributed_to'))
                    ->placeholder('—')
                    ->searchable()
                    ->extraCellAttributes(['class' => 'fi-apt-stacked-title']),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y - H:i')
                    ->label(__('panel-app::resources.credits.columns.purchased_at'))
                    ->sortable()
                    ->extraCellAttributes([
                        'data-apt-label' => __('panel-app::resources.credits.columns.date'),
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-app::resources.credits.columns.status'))
                    ->options(UserCreditStatusEnum::class)
                    ->multiple(),
            ])
            ->headerActions([
                PurchaseCreditsAction::make()
                    ->label(__('panel-app::resources.credits.history.purchase'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->forBillable(auth()->user(), static::getUrl(), static::getUrl()),
            ])
            ->stackedOnMobile();
    }
}
