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
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\CreateContractualPlan;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\EditContractualPlan;
use TresPontosTech\Admin\Filament\Resources\ContractualPlans\Pages\ListContractualPlans;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use UnitEnum;

class ContractualPlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $slug = 'contractual-plans';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'Planos Contratuais';

    protected static ?string $modelLabel = 'Plano Contratual';

    protected static ?string $pluralModelLabel = 'Planos Contratuais';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                TextInput::make('slug')
                    ->label('Slug')
                    ->readOnly(),

                TextInput::make('description')
                    ->label('Descrição')
                    ->required(),

                Select::make('type')
                    ->label('Tipo')
                    ->enum(BillableTypeEnum::class)
                    ->options(BillableTypeEnum::class)
                    ->required(),

                Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50),
                ToggleColumn::make('active')
                    ->label('Ativo'),
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
