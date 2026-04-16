<?php

namespace TresPontosTech\App\Filament\Resources\SharedDocuments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\CreateSharedDocument;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\EditSharedDocument;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\ListSharedDocuments;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Schemas\SharedDocumentForm;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Tables\SharedDocumentsTable;
use TresPontosTech\Consultants\Models\Document;
use UnitEnum;

class SharedDocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $slug = 'shared-documents';

    protected static ?string $label = 'Materiais';

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Document;

    public static function form(Schema $schema): Schema
    {
        return SharedDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedDocumentsTable::table($table);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('panel-admin::resources.navigation_group.appointments');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSharedDocuments::route('/'),
            'create' => CreateSharedDocument::route('/create'),
            'edit' => EditSharedDocument::route('/{record}/edit'),
        ];
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
