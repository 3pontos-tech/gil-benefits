<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
use TresPontosTech\Appointments\Mail\AppointmentConsultantUnassignedMail;
use TresPontosTech\Appointments\Mail\AppointmentRequestedAdminMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
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

describe('AppointmentRequestedAdminMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $appointment->loadMissing('user');

        $mailable = new AppointmentRequestedAdminMail($appointment);

        $mailable->assertHasSubject(__('appointments::mail.requested_admin.subject'));
    });

    it('renders employee name, category and requested date in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $appointment = Appointment::factory()->recycle($user)->create();

        $appointment->loadMissing('user');

        $mailable = new AppointmentRequestedAdminMail($appointment);

        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml((string) $appointment->category_type->getLabel());
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('does not reference a consultant', function (): void {
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($consultant)->create();
        $appointment->loadMissing('user');

        $mailable = new AppointmentRequestedAdminMail($appointment);

        $mailable->assertDontSeeInHtml('Ana Lima');
    });

    it('renders notes when present', function (): void {
        $appointment = Appointment::factory()->create(['notes' => 'Preciso de ajuda com investimentos']);
        $appointment->loadMissing('user');

        $mailable = new AppointmentRequestedAdminMail($appointment);

        $mailable->assertSeeInHtml('Preciso de ajuda com investimentos');
    });

    it('is queued to the configured admin recipients', function (): void {
        Mail::fake();
        config(['appointments.admin_notification_recipients' => ['atendimento@flammabeneficios.com.br', 'renan@firece.com.br']]);

        $appointment = Appointment::factory()->create();
        $appointment->loadMissing('user');

        Mail::to(config('appointments.admin_notification_recipients'))->queue(new AppointmentRequestedAdminMail($appointment));

        Mail::assertQueued(
            AppointmentRequestedAdminMail::class,
            fn (AppointmentRequestedAdminMail $mail): bool => $mail->hasTo('atendimento@flammabeneficios.com.br')
                && $mail->hasTo('renan@firece.com.br'),
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

    it('renders fallback when consultant is null', function (): void {
        $appointment = Appointment::factory()->withoutConsultant()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentCancelledMail($appointment);

        $mailable->assertSeeInHtml(__('appointments::mail.no_consultant'));
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

describe('AppointmentUserCancelledLateMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentUserCancelledLateMail($appointment);

        $mailable->assertHasSubject(__('appointments::mail.user_cancelled_late.subject'));
    });

    it('renders user name, consultant name and appointment date in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($user, $consultant)->create();

        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentUserCancelledLateMail($appointment);

        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml('Ana Lima');
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('renders fallback when consultant is null', function (): void {
        $appointment = Appointment::factory()->withoutConsultant()->create();
        $appointment->loadMissing(['user', 'consultant']);

        $mailable = new AppointmentUserCancelledLateMail($appointment);

        $mailable->assertSeeInHtml(__('appointments::mail.no_consultant'));
    });
});

describe('AppointmentConsultantUnassignedMail', function (): void {
    it('has correct subject', function (): void {
        $appointment = Appointment::factory()->create();
        $previous = Consultant::factory()->create();

        $mailable = new AppointmentConsultantUnassignedMail($appointment, $previous);

        $mailable->assertHasSubject(__('appointments::mail.consultant_unassigned.subject'));
    });

    it('renders the previous consultant name, the user name and the appointment date', function (): void {
        $user = User::factory()->create(['name' => 'Joao Silva']);
        $previous = Consultant::factory()->create(['name' => 'Ana Lima']);
        $appointment = Appointment::factory()->recycle($user)->create();

        $mailable = new AppointmentConsultantUnassignedMail($appointment, $previous);

        $mailable->assertSeeInHtml('Ana Lima');
        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml($appointment->appointment_at->format('d/m/Y'));
    });

    it('is queued to the previous consultant email', function (): void {
        Mail::fake();

        $previous = Consultant::factory()->create(['email' => 'previous@workspace.com']);
        $appointment = Appointment::factory()->create();

        Mail::to($previous->email)->queue(new AppointmentConsultantUnassignedMail($appointment, $previous));

        Mail::assertQueued(
            AppointmentConsultantUnassignedMail::class,
            fn ($mail): bool => $mail->hasTo('previous@workspace.com')
        );
    });
});
