<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

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
            ]);
    }
}
