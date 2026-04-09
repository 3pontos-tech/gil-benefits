<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\CreateDocument;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\EditDocument;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\ListDocuments;
use TresPontosTech\Consultants\Filament\Resources\Documents\RelationManagers\SharedDocumentRelationManager;
use TresPontosTech\Consultants\Filament\Resources\Documents\Schemas\DocumentForm;
use TresPontosTech\Consultants\Filament\Resources\Documents\Tables\DocumentsTable;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $slug = 'documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Document;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::table($table);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel-consultant::resources.documents.model_label');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $consultantId = auth()->user()->consultant->id;

        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('documentable_id', $consultantId)
            ->where('documentable_type', (new Consultant)->getMorphClass());
    }

    /**
     * @return Builder<Document>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['documentable']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    /**
     * @param  Document  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            SharedDocumentRelationManager::class,
        ];
    }
}
