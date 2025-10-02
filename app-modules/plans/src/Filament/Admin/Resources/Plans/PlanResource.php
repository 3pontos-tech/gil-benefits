<?php

namespace TresPontosTech\Plans\Filament\Admin\Resources\Plans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Pages\CreatePlan;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Pages\EditPlan;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Pages\ListPlans;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Schemas\PlanForm;
use TresPontosTech\Plans\Filament\Admin\Resources\Plans\Tables\PlansTable;
use TresPontosTech\Plans\Models\Plan;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
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
}
