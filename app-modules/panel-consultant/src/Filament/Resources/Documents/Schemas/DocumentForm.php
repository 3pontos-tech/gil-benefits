<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('panel-consultant::resources.documents.form.heading'))
                    ->compact()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('panel-consultant::resources.documents.form.title'))
                                    ->required()
                                    ->columnSpan(3),

                                Toggle::make('active')
                                    ->label(__('panel-consultant::resources.documents.form.active'))
                                    ->default(true)
                                    ->required()
                                    ->inline(false)
                                    ->columnSpan(1),
                            ]),

                        SpatieMediaLibraryFileUpload::make('documents')
                            ->label(__('panel-consultant::resources.documents.form.files'))
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
