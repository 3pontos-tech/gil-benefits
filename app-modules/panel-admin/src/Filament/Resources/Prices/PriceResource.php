<?php

namespace TresPontosTech\Admin\Filament\Resources\Prices;

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
use TresPontosTech\Admin\Filament\Resources\Prices\Pages\CreatePrice;
use TresPontosTech\Admin\Filament\Resources\Prices\Pages\EditPrice;
use TresPontosTech\Admin\Filament\Resources\Prices\Pages\ListPrices;
use TresPontosTech\Billing\Core\Models\Price;

class PriceResource extends Resource
{
    protected static ?string $model = Price::class;

    protected static ?string $slug = 'prices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.billing');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make(__('panel-admin::resources.prices.sections.plan_type.title'))
                            ->description(__('panel-admin::resources.prices.sections.plan_type.description'))
                            ->key('section-plan-type')
                            ->schema([
                                Select::make('billing_plan_id')
                                    ->label(__('panel-admin::resources.prices.form.plan'))
                                    ->relationship('plan', 'name')
                                    ->searchable()
                                    ->required(),

                                TextInput::make('type')
                                    ->helperText(__('panel-admin::resources.prices.form.type_helper'))
                                    ->required(),

                                TextInput::make('billing_scheme')
                                    ->helperText(__('panel-admin::resources.prices.form.billing_scheme_helper'))
                                    ->required(),

                                TextInput::make('tiers_mode')
                                    ->helperText(__('panel-admin::resources.prices.form.tiers_mode_helper'))
                                    ->required(),
                            ])->columns(2),

                        Section::make(__('panel-admin::resources.prices.sections.pricing.title'))
                            ->description(__('panel-admin::resources.prices.sections.pricing.description'))
                            ->key('section-pricing')
                            ->schema([
                                TextInput::make('unit_amount_decimal')
                                    ->label(__('panel-admin::resources.prices.form.unit_amount'))
                                    ->suffix('¢')
                                    ->required()
                                    ->integer(),

                                TextInput::make('monthly_appointments')
                                    ->label(__('panel-admin::resources.prices.form.monthly_appointments'))
                                    ->numeric()
                                    ->helperText(__('panel-admin::resources.prices.form.monthly_appointments_helper'))
                                    ->required(),
                            ])->columns(2),

                        Section::make(__('panel-admin::resources.prices.sections.features.title'))
                            ->description(__('panel-admin::resources.prices.sections.features.description'))
                            ->key('section-features')
                            ->schema([
                                Toggle::make('active')
                                    ->helperText(__('panel-admin::resources.prices.form.active_helper'))
                                    ->required(),
                                Toggle::make('whatsapp_enabled')
                                    ->label(__('panel-admin::resources.prices.form.whatsapp_enabled'))
                                    ->required(),
                                Toggle::make('materials_enabled')
                                    ->label(__('panel-admin::resources.prices.form.materials_enabled'))
                                    ->required(),
                            ])->columns(3),

                        Section::make(__('panel-admin::resources.prices.sections.provider.title'))
                            ->description(__('panel-admin::resources.prices.sections.provider.description'))
                            ->key('section-provider')
                            ->schema([
                                TextInput::make('provider_price_id')
                                    ->readOnly()
                                    ->label(__('panel-admin::resources.prices.form.provider_price_id'))
                                    ->helperText(__('panel-admin::resources.prices.form.provider_price_id_helper'))
                                    ->required(),
                            ]),

                        Section::make(__('panel-admin::resources.prices.sections.metadata.title'))
                            ->description(__('panel-admin::resources.prices.sections.metadata.description'))
                            ->key('section-metadata')
                            ->schema([
                                CodeEditor::make('metadata')
                                    ->formatStateUsing(fn (?string $state): string => $state ? json_encode(json_decode($state), JSON_PRETTY_PRINT) : '')
                                    ->language(Language::Json)
                                    ->required(),
                            ])
                            ->columnSpanFull(),

                        Section::make(__('panel-admin::resources.prices.sections.auditing.title'))
                            ->description(__('panel-admin::resources.prices.sections.auditing.description'))
                            ->key('section-auditing')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('panel-admin::resources.prices.created_date'))
                                    ->state(fn (?Price $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                TextEntry::make('updated_at')
                                    ->label(__('panel-admin::resources.prices.last_modified_date'))
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
