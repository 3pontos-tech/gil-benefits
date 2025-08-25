<?php

namespace App\Filament\Admin\Resources\Consultants;

use App\Filament\Admin\Resources\Consultants\Pages\CreateConsultant;
use App\Filament\Admin\Resources\Consultants\Pages\EditConsultant;
use App\Filament\Admin\Resources\Consultants\Pages\ListConsultants;
use App\Filament\Admin\Resources\Consultants\Schemas\ConsultantForm;
use App\Filament\Admin\Resources\Consultants\Tables\ConsultantsTable;
use App\Models\Consultant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultantResource extends Resource
{
    protected static ?string $model = Consultant::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function form(Schema $schema): Schema
    {
        return ConsultantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsultantsTable::configure($table);
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
            'index' => ListConsultants::route('/'),
            'create' => CreateConsultant::route('/create'),
            'edit' => EditConsultant::route('/{record}/edit'),
        ];
    }
}
