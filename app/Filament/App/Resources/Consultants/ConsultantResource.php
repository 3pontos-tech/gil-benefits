<?php

namespace App\Filament\App\Resources\Consultants;

use App\Filament\App\Resources\Consultants\Pages\ListConsultants;
use App\Filament\App\Resources\Consultants\Pages\ViewConsultant;
use App\Models\Consultant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConsultantResource extends Resource
{
    protected static ?string $model = Consultant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Consultores';

    protected static bool $isScopedToTenant = false;

    public static function canView(Model $record): bool
    {
        return false;
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
            'view' => ViewConsultant::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'biography', 'short_description', 'tags.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $mediaUrl = $record->getFirstMediaUrl('avatars');

        return [
            'Sobre' => $record->short_description,
            'thumbnail' => empty($mediaUrl) ? 'https://placehold.co/600x400?text=' . str($record->name)->charAt(0) : $mediaUrl,
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
