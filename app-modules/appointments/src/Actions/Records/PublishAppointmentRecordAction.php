<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Records;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Mail\AppointmentRecordPublishedMail;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final readonly class PublishAppointmentRecordAction
{
    public function execute(AppointmentRecord $record, string $content): void
    {
        $wasUnpublished = $record->published_at === null;

        $record->update([
            'content' => $content,
            'published_at' => $record->published_at ?? now(),
        ]);

        if ($wasUnpublished) {
            Mail::to($record->appointment->user)
                ->queue(new AppointmentRecordPublishedMail($record));

            // TODO: notificar o cliente pela central de notificações in-app (Filament database notification)
            // quando a feature de central de notificações for implementada.
            // Notification::make()
            //     ->success()
            //     ->title(__('appointments::resources.appointments.records.notifications.published.title'))
            //     ->sendToDatabase($record->appointment->user)
            //     ->send();
        }
    }
}
