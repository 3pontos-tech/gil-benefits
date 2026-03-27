<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('consultant_id')
                    ->default(fn () => auth()->user()->consultant->getKey()),

                TextInput::make('title')
                    ->required(),
                Toggle::make('active')
                    ->default(true)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('documents')
                    ->label('Arquivo(s)')
                    ->collection('documents')
                    ->disk('public')
                    ->acceptedFileTypes(
                        collect(DocumentExtensionTypeEnum::cases())
                            ->map(fn (DocumentExtensionTypeEnum $type): string => $type->getMimeType())
                            ->all())
//                    ->visibility('private')
                    ->maxSize(20480)
                    ->required(),

            ]);
    }
}
