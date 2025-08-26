<?php

namespace App\Filament\Admin\Clusters\Partners\Resources\Consultants\Schemas;

use App\Enums\AvailableTagsEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ConsultantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Consultant')
                    ->columnSpanFull()
                    ->vertical()
                    ->activeTab(1)
                    ->tabs([
                        Tab::make('Preview')
                            ->hidden(fn ($operation): bool => $operation === 'create')
                            ->icon(Heroicon::OutlinedUser)
                            ->childComponents([
                                View::make('filament.shared.consultants.profile'),
                            ]),
                        Tab::make('Sobre')
                            ->icon(Heroicon::OutlinedDocumentArrowUp)
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->live(debounce: 300)
                                        ->afterStateUpdated(function ($set, $state): void {
                                            $set('slug', str($state ?? '')->slug());
                                        })
                                        ->required(),
                                    TextInput::make('slug')
                                        ->formatStateUsing(fn ($get) => str($get('name'))->slug),
                                    TextInput::make('phone')
                                        ->mask('(99) 99999-9999')
                                        ->required(),
                                    TextInput::make('email')
                                        ->email()
                                        ->required(),
                                    KeyValue::make('socials_urls')
                                        ->editableKeys(false)
                                        ->addable(false)
                                        ->deletable(false)
                                        ->formatStateUsing(fn ($operation, $state) => $operation === 'create' ? [
                                            'linkedin' => 'https://www.linkedin.com/in/',
                                            'instagram' => 'https://www.instagram.com/',
                                            'facebook' => 'https://www.facebook.com/',
                                            'twitter' => 'https://www.twitter.com/',
                                            'youtube' => 'https://www.youtube.com/',
                                            'website' => 'https://www.website.com/',
                                        ] : $state)
                                        ->columnSpanFull(),
                                ]),

                            ]),
                        Tab::make('Descritivos')
                            ->icon(Heroicon::OutlinedDocumentArrowUp)
                            ->schema([
                                TextInput::make('short_description')
                                    ->maxLength(255)
                                    ->hint('Senior Consultant blablabla')
                                    ->required(),
                                MarkdownEditor::make('biography')
                                    ->required()
                                    ->columnSpanFull(),

                                MarkdownEditor::make('readme')
                                    ->required()
                                    ->columnSpanFull(),

                            ]),

                        Tab::make('Fotos')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('avatar')
                                    ->collection('avatars'),
                            ]),

                        Tab::make('Tags')
                            ->icon(Heroicon::OutlinedTag)
                            ->schema(
                                collect(AvailableTagsEnum::cases())
                                    ->map(fn ($tag): SpatieTagsInput => SpatieTagsInput::make($tag->value)->type($tag->value))
                                    ->toArray()),

                    ]),
            ]);
    }
}
