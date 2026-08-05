<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AppointmentFeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('panel-admin::resources.appointment_feedbacks.sections.evaluation'))
                ->icon(Heroicon::Star)
                ->columns(2)
                ->schema([
                    TextEntry::make('rating')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.rating'))
                        ->badge()
                        ->formatStateUsing(fn (int $state): string => $state . '/5')
                        ->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state === 3 ? 'warning' : 'success')),
                    TextEntry::make('created_at')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.created_at'))
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('comment')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.comment'))
                        ->placeholder(__('panel-admin::resources.appointment_feedbacks.fields.no_comment'))
                        ->columnSpanFull(),
                ]),

            Section::make(__('panel-admin::resources.appointment_feedbacks.sections.appointment'))
                ->icon(Heroicon::CalendarDays)
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.user')),
                    TextEntry::make('appointment.consultant.name')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.consultant'))
                        ->placeholder('—'),
                    TextEntry::make('appointment.company.name')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.company'))
                        ->placeholder('—'),
                    TextEntry::make('appointment.appointment_at')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.appointment_at'))
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('appointment.status')
                        ->label(__('panel-admin::resources.appointment_feedbacks.fields.appointment_status'))
                        ->badge(),
                ]),
        ]);
    }
}
