<?php

namespace App\Filament\Admin\Clusters\Partners\Resources\Consultants;

use App\Filament\Admin\Clusters\Partners\PartnersCluster;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\CreateConsultant;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\EditConsultant;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Pages\ListConsultants;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Schemas\ConsultantForm;
use App\Filament\Admin\Clusters\Partners\Resources\Consultants\Tables\ConsultantsTable;
use App\Models\Consultant;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultantResource extends Resource
{
    protected static ?string $cluster = PartnersCluster::class;

    protected static ?string $model = Consultant::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

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
