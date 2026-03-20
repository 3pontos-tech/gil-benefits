<?php

namespace TresPontosTech\Admin\Filament\Resources\Companies;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Admin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\Admin\Filament\Resources\Companies\Pages\EditCompany;
use TresPontosTech\Admin\Filament\Resources\Companies\Pages\ListCompanies;
use TresPontosTech\Admin\Filament\Resources\Companies\RelationManagers\ContractualPlansRelationManager;
use TresPontosTech\Admin\Filament\Resources\Companies\RelationManagers\EmployeesRelationManager;
use TresPontosTech\Admin\Filament\Resources\Companies\Schemas\CompanyForm;
use TresPontosTech\Admin\Filament\Resources\Companies\Tables\CompaniesTable;
use TresPontosTech\Company\Models\Company;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.companies.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.companies.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.companies.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            EmployeesRelationManager::class,
            ContractualPlansRelationManager::class,
        ];
    }
}
