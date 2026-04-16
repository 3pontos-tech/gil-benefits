<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Actions\DownloadDocumentFilamentAction;
use TresPontosTech\Consultants\Models\Document;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('appointments::resources.appointments.infolist.appointment_info'))
                    ->icon(Heroicon::User)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label(__('appointments::resources.appointments.table.columns.user')),
                                TextEntry::make('consultant.name')
                                    ->label(__('appointments::resources.appointments.table.columns.consultant')),
                                TextEntry::make('category_type')
                                    ->label(__('appointments::resources.appointments.wizard.steps.category_type'))
                                    ->badge(),
                                TextEntry::make('status')
                                    ->label(__('appointments::resources.appointments.table.columns.status'))
                                    ->badge(),
                            ]),
                    ]),

                Section::make(__('appointments::resources.appointments.plural'))
                    ->icon(Heroicon::Calendar)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('appointment_at')
                                    ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                                    ->size(TextSize::Large)
                                    ->weight('bold')
                                    ->dateTime('d/m/Y \à\s H:i'),
                            ]),
                    ]),

                Section::make(__('appointments::resources.appointments.infolist.metadata'))
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('appointments::resources.appointments.table.columns.created_at'))
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label(__('appointments::resources.appointments.table.columns.updated_at'))
                                    ->since(),
                            ]),
                    ]),

                Section::make(__('appointments::resources.appointments.wizard.labels.notes'))
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        TextEntry::make('notes')
                            ->label('ㅤㅤ')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('appointments::resources.appointments.infolist.ai_generation'))
                    ->icon(Heroicon::Sparkles)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record): bool => $record->record !== null)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('record.model_used')
                                    ->label(__('appointments::resources.appointments.infolist.ai.model_used'))
                                    ->placeholder('—')
                                    ->badge(),
                                TextEntry::make('record.published_at')
                                    ->label(__('appointments::resources.appointments.infolist.ai.published_at'))
                                    ->placeholder(__('appointments::resources.appointments.infolist.ai.draft'))
                                    ->dateTime('d/m/Y \à\s H:i'),
                                TextEntry::make('record.input_tokens')
                                    ->label(__('appointments::resources.appointments.infolist.ai.input_tokens'))
                                    ->placeholder('—')
                                    ->numeric(),
                                TextEntry::make('record.output_tokens')
                                    ->label(__('appointments::resources.appointments.infolist.ai.output_tokens'))
                                    ->placeholder('—')
                                    ->numeric(),
                                TextEntry::make('record.total_tokens')
                                    ->label(__('appointments::resources.appointments.infolist.ai.total_tokens'))
                                    ->placeholder('—')
                                    ->state(fn ($record): ?int => $record->record?->input_tokens === null
                                        ? null
                                        : ($record->record->input_tokens + ($record->record->output_tokens ?? 0)))
                                    ->numeric(),
                            ]),
                        TextEntry::make('record.content')
                            ->label(__('appointments::resources.appointments.infolist.ai.content'))
                            ->placeholder('—')
                            ->markdown()
                            ->columnSpanFull(),
                        TextEntry::make('record.internal_summary')
                            ->label(__('appointments::resources.appointments.infolist.ai.internal_summary'))
                            ->placeholder('—')
                            ->markdown()
                Section::make(__('appointments::resources.appointments.infolist.employee_documents'))
                    ->icon(Heroicon::Document)
                    ->schema([
                        RepeatableEntry::make('repeater')
                            ->label(__('appointments::resources.appointments.infolist.employee_documents'))
                            ->getStateUsing(function (Appointment $record): Collection {
                                return Document::query()
                                    ->where('documents.documentable_id', $record->user_id)
                                    ->where('documents.documentable_type', '=', 'users')
                                    ->get();
                            })
                            ->schema([
                                TextEntry::make('title')
                                    ->label(__('appointments::resources.appointments.infolist.documents.title')),
                                TextEntry::make('type')
                                    ->label(__('appointments::resources.appointments.infolist.documents.type'))
                                    ->badge()
                                    ->hintAction(DownloadDocumentFilamentAction::make()),
                            ])
                            ->columns(2)
                            ->placeholder(__('appointments::resources.appointments.infolist.documents.empty'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
