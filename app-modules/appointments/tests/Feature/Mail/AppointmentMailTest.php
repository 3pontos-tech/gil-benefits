<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

describe('AppointmentScheduledMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentScheduledMail($appointment);

        $mailable->assertHasSubject(__('appointments::mail.scheduled.subject'));
    });

    it('renders consultant name, user name and appointment date in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($user, $consultant)->create();

        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentScheduledMail($appointment);

        $mailable->assertSeeInHtml('Ana Lima');
        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('renders meeting url when present', function (): void {
        $appointment = Appointment::factory()->create(['meeting_url' => 'https://meet.example.com/test']);
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentScheduledMail($appointment);

        $mailable->assertSeeInHtml('https://meet.example.com/test');
    });

    it('omits meeting url section when absent', function (): void {
        $appointment = Appointment::factory()->create(['meeting_url' => null]);
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentScheduledMail($appointment);

        $mailable->assertDontSeeInHtml('Link da reunião');
    });

    it('is queued to the consultant email', function (): void {
        Mail::fake();

        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        Mail::to($appointment->consultant->email)->queue(new AppointmentScheduledMail($appointment));

        Mail::assertQueued(
            AppointmentScheduledMail::class,
            fn (AppointmentScheduledMail $mail) => $mail->hasTo($appointment->consultant->email),
        );
    });
});

describe('AppointmentCompletedMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentCompletedMail($appointment);

        $mailable->assertHasSubject(__('appointments::mail.completed.subject'));
    });

    it('renders user name, consultant name and appointment date in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($user, $consultant)->create();

        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentCompletedMail($appointment);

        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml('Ana Lima');
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('is queued to the user email', function (): void {
        Mail::fake();

        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        Mail::to($appointment->user->email)->queue(new AppointmentCompletedMail($appointment));

        Mail::assertQueued(
            AppointmentCompletedMail::class,
            fn (AppointmentCompletedMail $mail) => $mail->hasTo($appointment->user->email),
        );
    });
});

describe('AppointmentCancelledMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentCancelledMail($appointment);

        $mailable->assertHasSubject(__('appointments::mail.cancelled.subject'));
    });

    it('renders user name, consultant name and appointment date in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($user, $consultant)->create();

        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentCancelledMail($appointment);

        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml('Ana Lima');
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('is queued to the user email', function (): void {
        Mail::fake();

        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        Mail::to($appointment->user->email)->queue(new AppointmentCancelledMail($appointment));

        Mail::assertQueued(
            AppointmentCancelledMail::class,
            fn (AppointmentCancelledMail $mail) => $mail->hasTo($appointment->user->email),
        );
    });
});
