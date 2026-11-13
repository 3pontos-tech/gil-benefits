<?php

use App\Models\Users\User;
use TresPontosTech\Appointments\Mail\EmployeeOnboardingMail;
use TresPontosTech\Appointments\Models\Appointment;

describe('EmployeeOnboardingMail', function (): void {
    it('goes to the emails queue after the commit', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        expect($mail->queue)->toBe('emails')
            ->and($mail->afterCommit)->toBeTrue();
    });

    it('has the onboarding subject', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        $mail->assertHasSubject(__('appointments::mail.onboarding.subject', ['app' => config('app.name')]));
    });

    it('fills every placeholder, including the signature', function (): void {
        config()->set('app.name', 'Flamma');
        $html = (new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: false))->render();

        expect($html)->toContain('Equipe Flamma')
            ->not->toMatch('/:(app|name|days|hours|subject|number)\b/');
    });

    it('greets the employee by name and links to the app login', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(['name' => 'Paula Araujo']), hasTemporaryPassword: true);

        $mail->assertSeeInHtml('Paula Araujo');
        $mail->assertSeeInHtml('href="' . route('filament.app.auth.login') . '"', false);
    });

    it('points to the welcome email and the temporary password when the company created the access', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        $mail->assertSeeInHtml(__('user::mail.welcome.subject', ['app_name' => config('app.name')]));
        $mail->assertSeeInHtml('senha temporária');
        $mail->assertDontSeeInHtml('a senha que você cadastrou');
    });

    it('asks for the chosen password when the employee registered alone', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: false);

        $mail->assertSeeInHtml('a senha que você cadastrou');
        $mail->assertDontSeeInHtml('senha temporária');
    });

    it('quotes the same windows the booking rules use', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        $mail->assertSeeInHtml('começam ' . Appointment::BOOKING_LEAD_DAYS . ' dias depois de hoje');
        $mail->assertSeeInHtml('pelo menos ' . Appointment::CANCELLATION_WINDOW_HOURS . ' horas de antecedência. Fora desse prazo');
        $mail->assertSeeInHtml('também com pelo menos ' . Appointment::RESCHEDULE_WINDOW_HOURS . ' horas');
        $mail->assertSeeInHtml('em até ' . EmployeeOnboardingMail::FIRST_BOOKING_DEADLINE_DAYS . ' dias');
    });

    it('loads every image from the app over an absolute url, never inline', function (): void {
        $html = (new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true))->render();

        preg_match_all('/<img[^>]*src="([^"]+)"/', $html, $images);

        expect($images[1])->toHaveCount(7)
            ->each->toStartWith(asset('images/emails/'))
            ->and($html)->not->toContain('data:image');

        foreach ($images[1] as $src) {
            expect(public_path(str_replace(asset(''), '', $src)))->toBeFile();
        }
    });
});
