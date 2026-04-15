<?php

namespace TresPontosTech\Consultants\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('appointments::resources.appointments.table.columns.user')),
                        TextEntry::make('category_type')
                            ->label(__('appointments::resources.appointments.wizard.steps.category_type'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('appointments::resources.appointments.table.columns.status'))
                            ->badge(),
                    ]),

                Section::make(__('appointments::resources.appointments.plural'))
                    ->icon(Heroicon::Calendar)
                    ->schema([
                        TextEntry::make('appointment_at')
                            ->label(__('appointments::resources.appointments.table.columns.appointment_at'))
                            ->size(TextSize::Large)
                            ->weight('bold')
                            ->dateTime('d/m/Y \à\s H:i'),
                    ]),

                Section::make(__('appointments::resources.appointments.wizard.labels.notes'))
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        TextEntry::make('notes')
                            ->label('ㅤㅤ')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('panel-consultant::resources.appointments.infolist.financial_profile'))
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->description(__('panel-consultant::resources.appointments.infolist.financial_profile_description'))
                    ->collapsible()
                    ->visible(fn ($record): bool => $record->user?->anamnese !== null)
                    ->schema([
                        ViewEntry::make('anamnese')
                            ->label('')
                            ->view('panel-consultant::infolists.entries.client-anamnese')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('panel-consultant::resources.appointments.infolist.financial_profile'))
                    ->icon(Heroicon::ExclamationTriangle)
                    ->visible(fn ($record): bool => $record->user?->anamnese === null)
                    ->schema([
                        TextEntry::make('no_anamnese')
                            ->label(__('panel-consultant::resources.appointments.infolist.no_anamnese'))
                            ->state(__('panel-consultant::resources.appointments.infolist.no_anamnese_description'))
                            ->columnSpanFull(),
                    ]),

                Section::make('repeater')
                    ->label(__('appointments::resources.appointments.infolist.employee_documents'))
                    ->icon(Heroicon::Document)
                    ->schema([
                        RepeatableEntry::make(__('appointments::resources.appointments.infolist.employee_documents'))
                            ->label('')
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
