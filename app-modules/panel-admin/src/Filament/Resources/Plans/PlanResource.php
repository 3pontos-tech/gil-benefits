<?php

namespace TresPontosTech\Admin\Filament\Resources\Plans;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use TresPontosTech\Admin\Filament\Resources\Plans\Pages\CreatePlan;
use TresPontosTech\Admin\Filament\Resources\Plans\Pages\EditPlan;
use TresPontosTech\Admin\Filament\Resources\Plans\Pages\ListPlans;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $slug = 'plans';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('provider')
                    ->disabled()
                    ->required(),
                TextInput::make('provider_product_id')
                    ->disabled()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->disabled()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->disabled()
                    ->required()
                    ->unique(Plan::class, 'slug', fn ($record) => $record),
                TextInput::make('description')
                    ->disabled()
                    ->required(),

                Select::make('type')
                    ->enum(BillableTypeEnum::class)
                    ->options(BillableTypeEnum::class)
                    ->required(),

                Section::make('Behavior')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        CheckboxList::make('has_generic_trial')
                            ->label('Has a generic trial period')
                            ->options([
                                'yes' => 'Yes',
                                'no' => 'No',
                            ])
                            ->descriptions([
                                'yes' => 'The trial period will be the same for all users.',
                                'no' => 'The trial period will be unique for each user.',
                            ]),

                        CheckboxList::make('allow_promotion_codes')
                            ->label('Allow Promotion Codes')
                            ->options([
                                'yes' => 'Yes',
                                'no' => 'No',
                            ])
                            ->descriptions([
                                'yes' => 'Promotion codes can be applied to this plan.',
                                'no' => 'No promotion codes can be applied to this plan.',
                            ]),

                        CheckboxList::make('collect_tax_ids')
                            ->label('Collect Tax IDs')
                            ->options([
                                'yes' => 'Yes',
                                'no' => 'No',
                            ])
                            ->descriptions([
                                'yes' => 'Tax IDs will be collected for this plan.',
                                'no' => 'Tax IDs will not be collected for this plan.',
                            ]),
                    ]),

                TextInput::make('trial_days')
                    ->integer(),

                TextInput::make('unit_label')
                    ->required(),

                TextInput::make('statement_descriptor')
                    ->required(),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn (?Plan $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn (?Plan $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('provider')
                    ->badge()
                    ->description(fn (Plan $record) => $record->provider_product_id),
                TextColumn::make('name')
                    ->description(fn (Plan $record) => $record->slug)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prices_count')
                    ->counts('prices'),
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
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }
}
