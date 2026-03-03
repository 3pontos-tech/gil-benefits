<?php

namespace TresPontosTech\Billing\Core\Filament\Admin\Resources\Prices;

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
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                Grid::make(2)
                    ->schema([
                        Section::make('Plan & Type')
                            ->description('Select the plan and define how this price should be billed.')
                            ->key('section-plan-type')
                            ->schema([
                                Select::make('billing_plan_id')
                                    ->label('Plan')
                                    ->relationship('plan', 'name')
                                    ->searchable()
                                    ->required(),

                                TextInput::make('type')
                                    ->helperText('e.g. recurring, one_time')
                                    ->required(),

                                TextInput::make('billing_scheme')
                                    ->helperText('How the billing is calculated (per_unit, tiered, etc).')
                                    ->required(),

                                TextInput::make('tiers_mode')
                                    ->helperText('If billing is tiered, choose the mode (graduated, volume).')
                                    ->required(),
                            ])->columns(2),

                        Section::make('Pricing')
                            ->description('Set the price amount and included usage.')
                            ->key('section-pricing')
                            ->schema([
                                TextInput::make('unit_amount_decimal')
                                    ->label('Unit Amount (cents)')
                                    ->suffix('¢')
                                    ->required()
                                    ->integer(),

                                TextInput::make('monthly_appointments')
                                    ->label('Monthly Appointments')
                                    ->numeric()
                                    ->helperText('How many appointments are included per month.')
                                    ->required(),
                            ])->columns(2),

                        Section::make('Features')
                            ->description('Toggle which features are included for this price.')
                            ->key('section-features')
                            ->schema([
                                Toggle::make('active')
                                    ->helperText('Whether this price can be purchased.')
                                    ->required(),
                                Toggle::make('whatsapp_enabled')
                                    ->label('WhatsApp Enabled')
                                    ->required(),
                                Toggle::make('materials_enabled')
                                    ->label('Materials Enabled')
                                    ->required(),
                            ])->columns(3),

                        Section::make('Provider')
                            ->description('External payment provider references.')
                            ->key('section-provider')
                            ->schema([
                                TextInput::make('provider_price_id')
                                    ->readOnly()
                                    ->label('Provider Price ID')
                                    ->helperText('Identifier of this price on the payment provider (e.g., Stripe).')
                                    ->required(),
                            ]),

                        Section::make('Metadata')
                            ->description('Structured JSON metadata associated with this price.')
                            ->key('section-metadata')
                            ->schema([
                                CodeEditor::make('metadata')
                                    ->formatStateUsing(fn (?string $state): string => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '')
                                    ->language(Language::Json)
                                    ->required(),
                            ])
                            ->columnSpanFull(),

                        Section::make('Auditing')
                            ->description('Automatically tracked timestamps.')
                            ->key('section-auditing')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created Date')
                                    ->state(fn (?Price $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                TextEntry::make('updated_at')
                                    ->label('Last Modified Date')
                                    ->state(fn (?Price $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('billing_scheme')
                    ->badge(),

                TextColumn::make('tiers_mode')
                    ->badge(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('unit_amount_decimal')

                    ->money(currency: 'BRL', divideBy: 100),

                ToggleColumn::make('active'),
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
        return parent::getGlobalSearchEloquentQuery()->with(['plan']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['plan.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->plan) {
            $details['plan'] = $record->plan->name;
        }

        return $details;
    }
}
