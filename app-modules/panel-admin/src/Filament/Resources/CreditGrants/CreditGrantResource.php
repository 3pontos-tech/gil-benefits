<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\CreditGrants;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Billing\Core\Actions\Credit\RevokeCreditGrant;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\PanelAdmin\Filament\Resources\CreditGrants\Pages\ListCreditGrants;

class CreditGrantResource extends Resource
{
    protected static ?string $model = CreditGrant::class;

    protected static ?string $slug = 'credit-grants';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Gift;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.credit_grants.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.credit_grants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.credit_grants.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.credit_grants.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label(__('panel-admin::resources.credit_grants.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('targetUser.name')
                    ->label(__('panel-admin::resources.credit_grants.fields.target'))
                    ->placeholder(__('panel-admin::resources.credit_grants.target_company'))
                    ->badge(),

                TextColumn::make('quantity')
                    ->label(__('panel-admin::resources.credit_grants.fields.quantity'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('justification')
                    ->label(__('panel-admin::resources.credit_grants.fields.justification'))
                    ->wrap()
                    ->limit(80),

                TextColumn::make('admin.name')
                    ->label(__('panel-admin::resources.credit_grants.fields.admin'))
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label(__('panel-admin::resources.credit_grants.fields.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('targetUser')
                    ->label(__('panel-admin::resources.credit_grants.fields.target'))
                    ->relationship('targetUser', 'name')
                    ->searchable(),

                SelectFilter::make('admin')
                    ->label(__('panel-admin::resources.credit_grants.fields.admin'))
                    ->relationship('admin', 'name')
                    ->searchable(),

                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('panel-admin::resources.credit_grants.filters.from'))
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label(__('panel-admin::resources.credit_grants.filters.until'))
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('revoke')
                    ->label(__('panel-admin::resources.credit_grants.actions.revoke.label'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(fn (CreditGrant $record): string => self::revokeDescription($record))
                    ->action(fn (CreditGrant $record) => resolve(RevokeCreditGrant::class)->handle($record)),
            ]);
    }

    private static function revokeDescription(CreditGrant $record): string
    {
        $available = $record->userCredits()
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        $locked = $record->userCredits()
            ->whereIn('status', [UserCreditStatusEnum::InUse, UserCreditStatusEnum::Used])
            ->count();

        $message = (string) __('panel-admin::resources.credit_grants.actions.revoke.will_revoke', ['available' => $available]);

        if ($locked > 0) {
            $message .= ' ' . __('panel-admin::resources.credit_grants.actions.revoke.locked_notice', ['locked' => $locked]);
        }

        return $message;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditGrants::route('/'),
        ];
    }
}
