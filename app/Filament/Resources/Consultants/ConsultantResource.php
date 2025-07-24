<?php

namespace App\Filament\Resources\Consultants;

use App\Filament\Resources\Consultants\Pages\CreateConsultant;
use App\Filament\Resources\Consultants\Pages\EditConsultant;
use App\Filament\Resources\Consultants\Pages\ListConsultants;
use App\Filament\Resources\Consultants\Schemas\ConsultantForm;
use App\Filament\Resources\Consultants\Tables\ConsultantsTable;
use App\Models\Consultant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConsultantResource extends Resource
{
    protected static ?string $model = Consultant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function form(Schema $schema): Schema
    {
        return ConsultantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsultantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
