<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Users\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Personal extra-credit gifts the admin donated directly to this user
 * ({@see CreditGrant} with target_user_id set).
 */
class CreditGrantsRelationManager extends RelationManager
{
    protected static string $relationship = 'receivedCreditGrants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::resources.credit_grants.relation_manager.user_title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('justification')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('panel-admin::resources.credit_grants.fields.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('panel-admin::resources.credit_grants.fields.quantity'))
                    ->numeric(),

                TextColumn::make('company.name')
                    ->label(__('panel-admin::resources.credit_grants.fields.company')),

                TextColumn::make('justification')
                    ->label(__('panel-admin::resources.credit_grants.fields.justification'))
                    ->wrap()
                    ->limit(80),

                TextColumn::make('admin.name')
                    ->label(__('panel-admin::resources.credit_grants.fields.admin')),
            ])
            ->filters([
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
            ->headerActions([])
            ->recordActions([]);
    }
}
