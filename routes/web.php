<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Route;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Mail\DocumentSharedMail;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\User\Mail\WelcomeUserMail;

/*
|--------------------------------------------------------------------------
| Email Preview Routes (local development only)
|--------------------------------------------------------------------------
*/
if (app()->isLocal()) {
    Route::prefix('mail-preview')->name('mail-preview.')->group(function (): void {
        Route::get('/appointment-scheduled/{appointment}', function (Appointment $appointment): AppointmentScheduledMail {
            $appointment->loadMissing(['user', 'consultant']);

            return new AppointmentScheduledMail($appointment);
        })->name('appointment-scheduled');

        Route::get('/appointment-completed/{appointment}', function (Appointment $appointment): AppointmentCompletedMail {
            $appointment->loadMissing(['user', 'consultant']);

            return new AppointmentCompletedMail($appointment);
        })->name('appointment-completed');

        Route::get('/appointment-cancelled/{appointment}', function (Appointment $appointment): AppointmentCancelledMail {
            $appointment->loadMissing(['user', 'consultant']);

            return new AppointmentCancelledMail($appointment);
        })->name('appointment-cancelled');

        Route::get('/welcome/{user}', function (User $user): WelcomeUserMail {
            return new WelcomeUserMail($user);
        })->name('welcome');

        Route::get('/document-shared/{document}/{employee}', function (Document $document, User $employee): DocumentSharedMail {
            $document->loadMissing('documentable');

            return new DocumentSharedMail($document, $employee);
        })->name('document-shared');
    });
}
