<?php

namespace TresPontosTech\Admin\Filament\Resources\ContractualPlans;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\CreateContractualPlan;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\EditContractualPlan;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\ListContractualPlans;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class ContractualPlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $slug = 'contractual-plans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.contractual_plans.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.contractual_plans.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.contractual_plans.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel-admin::resources.contractual_plans.form.name'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label(__('panel-admin::resources.contractual_plans.form.slug'))
                    ->readOnly(),

                TextInput::make('description')
                    ->label(__('panel-admin::resources.contractual_plans.form.description'))
                    ->required(),

                Select::make('type')
                    ->label(__('panel-admin::resources.contractual_plans.form.type'))
                    ->enum(BillableTypeEnum::class)
                    ->options(BillableTypeEnum::class)
                    ->required(),

                Toggle::make('active')
                    ->label(__('panel-admin::resources.contractual_plans.form.active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('panel-admin::resources.contractual_plans.table.type'))
                    ->badge(),
                TextColumn::make('name')
                    ->label(__('panel-admin::resources.contractual_plans.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('panel-admin::resources.contractual_plans.table.description'))
                    ->limit(50),
                ToggleColumn::make('active')
                    ->label(__('panel-admin::resources.contractual_plans.table.active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractualPlans::route('/'),
            'create' => CreateContractualPlan::route('/create'),
            'edit' => EditContractualPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('provider', BillingProviderEnum::Contractual);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
