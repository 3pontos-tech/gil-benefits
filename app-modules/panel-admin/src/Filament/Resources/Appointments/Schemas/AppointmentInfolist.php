<?php

namespace TresPontosTech\Admin\Filament\Resources\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações da Consultoria')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Cliente'),
                                TextEntry::make('consultant.name')
                                    ->label('Consultor'),
                                TextEntry::make('category_type')
                                    ->label('Categoria')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                            ]),
                    ]),

                Section::make('Agendamento')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('appointment_at')
                                    ->label('Data da Consultoria')
                                    ->size(TextSize::Large)
                                    ->weight('bold')
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->since(),
                            ]),
                    ]),

                Section::make('Acompanhamento')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Atualizado em')
                                    ->since(),
                            ]),
                    ]),

                Section::make('Observações')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('notes')
                            ->label(__('appointments::resources.appointments.wizard.labels.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
