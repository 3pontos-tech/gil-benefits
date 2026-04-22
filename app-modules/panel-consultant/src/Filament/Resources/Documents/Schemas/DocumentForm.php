<?php

namespace TresPontosTech\Consultants\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

                        ToggleButtons::make('_document_type')
                            ->hiddenLabel()
                            ->options([
                                'file' => __('panel-consultant::resources.documents.form.tab_file'),
                                'link' => __('panel-consultant::resources.documents.form.tab_link'),
                            ])
                            ->default('file')
                            ->grouped()
                            ->live()
                            ->dehydrated(false)
                            ->helperText(__('panel-consultant::resources.documents.form.type_hint'))
                            ->afterStateHydrated(fn (Set $set, $record): mixed => $set('_document_type', filled($record?->link) ? 'link' : 'file'))
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('documents')
                            ->label(__('panel-consultant::resources.documents.form.files'))
                            ->collection('documents')
                            ->acceptedFileTypes(
                                collect(DocumentExtensionTypeEnum::cases())
                                    ->map(fn (DocumentExtensionTypeEnum $type): string => $type->getMimeType())
                                    ->all()
                            )
                            ->maxSize(102400)
                            ->hiddenJs(<<<'JS'
                                $get('_document_type') === 'link'
                            JS)
                            ->required(fn (Get $get): bool => $get('_document_type') === 'file')
                            ->columnSpanFull(),

                        TextInput::make('link')
                            ->label(__('panel-consultant::resources.documents.form.link'))
                            ->url()
                            ->hiddenJs(<<<'JS'
                                $get('_document_type') !== 'link'
                            JS)
                            ->dehydrateStateUsing(fn (Get $get, $state) => $get('_document_type') === 'link' ? $state : null)
                            ->required(fn (Get $get): bool => $get('_document_type') === 'link')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
