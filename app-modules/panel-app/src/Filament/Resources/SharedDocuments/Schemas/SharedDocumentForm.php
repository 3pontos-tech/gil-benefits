<?php

namespace TresPontosTech\App\Filament\Resources\SharedDocuments\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;

class SharedDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('panel-app::resources.documents.form.heading'))
                    ->compact()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('panel-app::resources.documents.form.title'))
                                    ->required()
                                    ->columnSpan(3),

                            ]),

                        SpatieMediaLibraryFileUpload::make('documents')
                            ->label(__('panel-app::resources.documents.form.files'))
                            ->collection('documents')
                            ->acceptedFileTypes(
                                collect(DocumentExtensionTypeEnum::cases())
                                    ->map(fn (DocumentExtensionTypeEnum $type): string => $type->getMimeType())
                                    ->all()
                            )
                            ->maxSize(102400)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
