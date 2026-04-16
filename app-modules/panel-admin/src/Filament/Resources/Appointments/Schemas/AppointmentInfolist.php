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
use TresPontosTech\User\Enums\LifeMoment;

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

                Section::make(__('appointments::resources.appointments.infolist.anamnese'))
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->collapsible()
                    ->visible(fn ($record): bool => $record->user?->anamnese !== null)
                    ->schema([
                        TextEntry::make('user.anamnese.life_moment')
                            ->label(__('panel-app::anamnese.fields.life_moment'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state instanceof LifeMoment ? $state->getLabel() : LifeMoment::tryFrom((string) $state)?->getLabel() ?? $state),

                        TextEntry::make('user.anamnese.main_motivation')
                            ->label(__('panel-app::anamnese.fields.main_motivation'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('user.anamnese.money_relationship')
                            ->label(__('panel-app::anamnese.fields.money_relationship'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('user.anamnese.plans_monthly_expenses')
                            ->label(__('panel-app::anamnese.fields.plans_monthly_expenses'))
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('user.anamnese.tried_financial_strategies')
                            ->label(__('panel-app::anamnese.fields.tried_financial_strategies'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('appointments::resources.appointments.infolist.employee_documents'))
                    ->icon(Heroicon::Document)
                    ->schema([
                        RepeatableEntry::make('repeater')
                            ->label(__('appointments::resources.appointments.infolist.employee_documents'))
                            ->getStateUsing(function (Appointment $record): Collection {
                                if ($record->relationLoaded('_userOwnedDocuments')) {
                                    return $record->getRelation('_userOwnedDocuments');
                                }

                                $documents = Document::query()
                                    ->where('documents.documentable_id', $record->user_id)
                                    ->where('documents.documentable_type', '=', 'users')
                                    ->with(['media', 'documentable'])
                                    ->get();

                                $record->setRelation('_userOwnedDocuments', $documents);
                                $documents->each(fn (Document $doc) => $doc->media->each(fn ($m) => $m->setRelation('model', $doc)));

                                return $documents;
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

                        RepeatableEntry::make(__('appointments::resources.appointments.infolist.employee_shared_documents'))
                            ->label('')
                            ->getStateUsing(function (Appointment $record): Collection {
                                if ($record->relationLoaded('_userSharedDocuments')) {
                                    return $record->getRelation('_userSharedDocuments');
                                }

                                $documents = Document::query()
                                    ->whereHas('shares', function ($query) use ($record) {
                                        return $query->where('employee_id', $record->user_id)
                                            ->where('active', 1);

                                    })
                                    ->with(['media', 'documentable'])
                                    ->get();

                                $record->setRelation('_userSharedDocuments', $documents);
                                $documents->each(fn (Document $doc) => $doc->media->each(fn ($media) => $media->setRelation('model', $doc)));

                                return $documents;
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
