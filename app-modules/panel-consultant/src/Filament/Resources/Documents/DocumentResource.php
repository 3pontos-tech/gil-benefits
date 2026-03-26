<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents;

use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\ListDocuments;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\CreateDocument;
use TresPontosTech\Consultants\Filament\Resources\Documents\Pages\EditDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use TresPontosTech\Consultants\Document;
use TresPontosTech\Consultants\Filament\Resources\Documents\Schemas\DocumentForm;
use TresPontosTech\Consultants\Filament\Resources\Documents\Tables\DocumentsTable;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $slug = 'documents';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::table($table);
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @return Builder<Document>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['consultant']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'consultant.name'];
    }

    /**
     * @param  Document  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->consultant) {
            $details['Consultant'] = $record->consultant->name;
        }

        return $details;
    }
}
