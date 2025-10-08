<?php

namespace TresPontosTech\Company\Filament\Admin\Resources\Companies;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages\EditCompany;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Pages\ListCompanies;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Schemas\CompanyForm;
use TresPontosTech\Company\Filament\Admin\Resources\Companies\Tables\CompaniesTable;
use TresPontosTech\Company\Models\Company;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

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
}
