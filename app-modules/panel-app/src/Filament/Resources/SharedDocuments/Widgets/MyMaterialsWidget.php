<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Widgets;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages\CreateSharedDocument;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\Pages\EditSharedDocument;
use TresPontosTech\PanelConsultant\Filament\Actions\DownloadDocumentFilamentAction;

class MyMaterialsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::query()
                ->where('documentable_type', auth()->user()->getMorphClass())
                ->where('documentable_id', auth()->user()->getKey())
                ->with('media'))
            ->heading(__('panel-app::resources.documents.my_materials.heading'))
            ->description(__('panel-app::resources.documents.my_materials.description'))
            // Na toolbar (e não no header) para dividir a linha com busca e
            // filtros; o CSS de .fi-apt-inline-toolbar sobe essa linha para o
            // lado do heading e põe o botão por último, como no layout.
            ->toolbarActions([
                Action::make('new-document')
                    ->label(__('panel-app::resources.documents.my_materials.new_document'))
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->url(CreateSharedDocument::getUrl()),
            ])
            ->extraAttributes(['class' => 'fi-apt-inline-toolbar'])
            ->columns([
                Split::make([
                    TextColumn::make('type')
                        ->badge()
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->grow(false),
                    Stack::make([
                        TextColumn::make('title')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes(['class' => 'fi-apt-card-title'])
                            ->searchable(),
                        TextColumn::make('created_at')
                            ->dateTime('d/m/Y'),
                    ]),
                ]),
            ])
            ->contentGrid(['md' => 2, 'xl' => 4])
            ->recordClasses(fn (Document $record): string => 'fi-apt-doc-' . $record->type->value)
            ->recordActions([
                ActionGroup::make([
                    DownloadDocumentFilamentAction::make()
                        ->label(__('panel-app::resources.documents.actions.access')),
                    EditAction::make()
                        ->url(fn (Document $record): string => EditSharedDocument::getUrl(['record' => $record])),
                    DeleteAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([8, 16, 24])
            ->defaultPaginationPageOption(8);
    }
}
