<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices;

use Filament\Forms\Components\CodeEditor\Enums\Language;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\Pages\CreatePrice;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\Pages\EditPrice;
use TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices\Pages\ListPrices;
use TresPontosTech\Billing\Core\Models\Price;
use UnitEnum;

class PriceResource extends Resource
{
    protected static ?string $model = Price::class;

    protected static ?string $slug = 'prices';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('billing_plan_id')
                    ->relationship('billingPlan', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('billing_scheme')
                    ->required(),

                TextInput::make('tiers_mode')
                    ->required(),

                TextInput::make('type')
                    ->required(),

                TextInput::make('unit_amount_decimal')
                    ->required()
                    ->integer(),

                TextInput::make('active')
                    ->required(),

                TextInput::make('provider_price_id')
                    ->required(),

                CodeEditor::make('metadata')
                    ->formatStateUsing(fn (?string $state): string => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '')
                    ->language(Language::Json)
                    ->required(),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn (?Price $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn (?Price $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('billingPlan.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('billing_scheme'),

                TextColumn::make('tiers_mode'),

                TextColumn::make('type'),

                TextColumn::make('unit_amount_decimal'),

                TextColumn::make('active'),

                TextColumn::make('provider_price_id'),

                TextColumn::make('metadata'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrices::route('/'),
            'create' => CreatePrice::route('/create'),
            'edit' => EditPrice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['billingPlan']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['billingPlan.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->billingPlan) {
            $details['BillingPlan'] = $record->billingPlan->name;
        }

        return $details;
    }
}
