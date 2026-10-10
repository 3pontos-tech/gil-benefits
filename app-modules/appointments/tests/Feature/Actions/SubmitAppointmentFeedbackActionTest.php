<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Actions\SubmitAppointmentFeedbackAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\AppointmentFeedbackException;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

beforeEach(function (): void {
    $this->user = actingAsEmployee();
    $this->appointment = appointmentFor($this->user, AppointmentStatus::Completed, now()->subDay());
});

it('stores the rating and comment for a completed appointment', function (): void {
    $feedback = resolve(SubmitAppointmentFeedbackAction::class)->handle($this->appointment, $this->user, 5, 'Ótimo');

    expect($feedback->rating)->toBe(5)
        ->and($feedback->comment)->toBe('Ótimo')
        ->and($feedback->user_id)->toBe($this->user->getKey())
        ->and($feedback->appointment_id)->toBe($this->appointment->getKey());
});

it('stores a blank comment as null', function (): void {
    $feedback = resolve(SubmitAppointmentFeedbackAction::class)->handle($this->appointment, $this->user, 4, '');

    expect($feedback->comment)->toBeNull();
});

it('refuses an appointment that is not completed', function (): void {
    $active = appointmentFor($this->user, AppointmentStatus::Active, now()->addDay());

    expect(fn () => resolve(SubmitAppointmentFeedbackAction::class)->handle($active, $this->user, 5, null))
        ->toThrow(AppointmentFeedbackException::class, __('appointments::resources.appointments.exceptions.feedback_requires_completion'));
});

it('refuses a second feedback', function (): void {
    resolve(SubmitAppointmentFeedbackAction::class)->handle($this->appointment, $this->user, 5, null);

    expect(fn () => resolve(SubmitAppointmentFeedbackAction::class)->handle($this->appointment->fresh(), $this->user, 3, null))
        ->toThrow(AppointmentFeedbackException::class, __('appointments::resources.appointments.exceptions.feedback_already_given'));

    expect(AppointmentFeedback::query()->count())->toBe(1);
});

it('refuses a rating outside 1..5', function (int $rating): void {
    expect(fn () => resolve(SubmitAppointmentFeedbackAction::class)->handle($this->appointment, $this->user, $rating, null))
        ->toThrow(AppointmentFeedbackException::class, __('appointments::resources.appointments.exceptions.invalid_rating'));
})->with([0, 6]);
