<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditOrders;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Credits\Actions\SettleCreditOrder;
use TresPontosTech\Credits\Enums\CreditOrderStatusEnum;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\CreditsCluster;
use TresPontosTech\PanelAdmin\Filament\Clusters\Credits\Resources\CreditOrders\Pages\ListCreditOrders;

class CreditOrderResource extends Resource
{
    protected static ?string $model = CreditOrder::class;

    protected static ?string $slug = 'credit-orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $cluster = CreditsCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.credits');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.credit_orders.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.credit_orders.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.credit_orders.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = CreditOrder::query()
            ->where('status', CreditOrderStatusEnum::Pending)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.credit_orders.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('panel-admin::resources.credit_orders.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('billable.name')
                    ->label(__('panel-admin::resources.credit_orders.fields.billable'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('company.name')
                    ->label(__('panel-admin::resources.credit_orders.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('panel-admin::resources.credit_orders.fields.quantity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('credits_count')
                    ->label(__('panel-admin::resources.credit_orders.fields.issued'))
                    ->badge()
                    ->color(fn (CreditOrder $record): string => $record->credits_count === $record->quantity ? 'success' : 'danger'),

                TextColumn::make('amount_cents')
                    ->label(__('panel-admin::resources.credit_orders.fields.amount'))
                    ->money('BRL', divideBy: 100)
                    ->sortable(),

                TextColumn::make('provider')
                    ->label(__('panel-admin::resources.credit_orders.fields.provider'))
                    ->badge(),

                TextColumn::make('paid_at')
                    ->label(__('panel-admin::resources.credit_orders.fields.paid_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('checkout_id')
                    ->label(__('panel-admin::resources.credit_orders.fields.checkout_id'))
                    ->placeholder(__('panel-admin::resources.credit_orders.no_checkout'))
                    ->copyable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-admin::resources.credit_orders.fields.status'))
                    ->options(CreditOrderStatusEnum::class),

                SelectFilter::make('provider')
                    ->label(__('panel-admin::resources.credit_orders.fields.provider'))
                    ->options(BillingProviderEnum::class),

                SelectFilter::make('company')
                    ->label(__('panel-admin::resources.credit_orders.fields.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('unfulfilled')
                    ->label(__('panel-admin::resources.credit_orders.filters.unfulfilled'))
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', CreditOrderStatusEnum::Paid)
                        ->whereDoesntHave('credits')),

                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('panel-admin::resources.credit_orders.filters.from'))
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label(__('panel-admin::resources.credit_orders.filters.until'))
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('settle')
                    ->label(__('panel-admin::resources.credit_orders.actions.settle.label'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('panel-admin::resources.credit_orders.actions.settle.heading'))
                    ->modalDescription(fn (CreditOrder $record): string => __('panel-admin::resources.credit_orders.actions.settle.description', [
                        'quantity' => $record->quantity,
                        'name' => $record->buyerName() ?? '—',
                    ]))
                    ->visible(fn (CreditOrder $record): bool => ! $record->isPaid())
                    ->action(fn (CreditOrder $record) => resolve(SettleCreditOrder::class)->handle($record->getKey())),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['billable', 'company'])
            ->withCount('credits');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditOrders::route('/'),
        ];
    }
}
