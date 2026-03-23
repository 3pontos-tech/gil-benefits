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
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $slug = 'plans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.plans.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.plans.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.plans.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.billing');
    }

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

                Section::make(__('panel-admin::resources.plans.behavior.title'))
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        CheckboxList::make('has_generic_trial')
                            ->label(__('panel-admin::resources.plans.behavior.has_generic_trial'))
                            ->options([
                                'yes' => __('panel-admin::resources.plans.behavior.yes'),
                                'no' => __('panel-admin::resources.plans.behavior.no'),
                            ])
                            ->descriptions([
                                'yes' => __('panel-admin::resources.plans.behavior.trial_same_for_all'),
                                'no' => __('panel-admin::resources.plans.behavior.trial_unique_per_user'),
                            ]),

                        CheckboxList::make('allow_promotion_codes')
                            ->label(__('panel-admin::resources.plans.behavior.allow_promotion_codes'))
                            ->options([
                                'yes' => __('panel-admin::resources.plans.behavior.yes'),
                                'no' => __('panel-admin::resources.plans.behavior.no'),
                            ])
                            ->descriptions([
                                'yes' => __('panel-admin::resources.plans.behavior.promotion_can_be_applied'),
                                'no' => __('panel-admin::resources.plans.behavior.no_promotion_codes'),
                            ]),

                        CheckboxList::make('collect_tax_ids')
                            ->label(__('panel-admin::resources.plans.behavior.collect_tax_ids'))
                            ->options([
                                'yes' => __('panel-admin::resources.plans.behavior.yes'),
                                'no' => __('panel-admin::resources.plans.behavior.no'),
                            ])
                            ->descriptions([
                                'yes' => __('panel-admin::resources.plans.behavior.tax_ids_collected'),
                                'no' => __('panel-admin::resources.plans.behavior.tax_ids_not_collected'),
                            ]),
                    ]),

                TextInput::make('trial_days')
                    ->integer(),

                TextInput::make('unit_label')
                    ->required(),

                TextInput::make('statement_descriptor')
                    ->required(),

                TextEntry::make('created_at')
                    ->label(__('panel-admin::resources.plans.created_date'))
                    ->state(fn (?Plan $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label(__('panel-admin::resources.plans.last_modified_date'))
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
            ])
            ->where('provider', BillingProviderEnum::Stripe);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }
}
