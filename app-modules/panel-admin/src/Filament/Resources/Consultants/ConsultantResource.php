<?php

namespace TresPontosTech\PanelAdmin\Filament\Resources\Consultants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\CreateConsultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\EditConsultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\ListConsultants;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\RelationManagers\SchedulesRelationManager;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Schemas\ConsultantForm;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Tables\ConsultantsTable;

class ConsultantResource extends Resource
{
    protected static ?string $model = Consultant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::resources.consultants.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::resources.consultants.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::resources.consultants.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

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
            SchedulesRelationManager::class,
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
