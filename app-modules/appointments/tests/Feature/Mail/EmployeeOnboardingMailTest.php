<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Mail\EmployeeOnboardingMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\travelTo;

function onboardingEmployeeOf(Company $company): User
{
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey(), ['role' => Roles::Employee->value]);

    return $user;
}

describe('EmployeeOnboardingMail', function (): void {
    it('goes to the emails queue after the commit', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        expect($mail->queue)->toBe('emails')
            ->and($mail->afterCommit)->toBeTrue();
    });

    it('waits five minutes, so the welcome email arrives first', function (): void {
        travelTo('2026-11-16 10:00:00');

        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        expect($mail->delay)->toEqual(Date::parse('2026-11-16 10:05:00'));
    });

    it('asks whoever still needs a plan to choose one, without promising access', function (): void {
        $user = onboardingEmployeeOf(Company::factory()->create(['slug' => Company::DEFAULT_SLUG]));

        $mail = new EmployeeOnboardingMail($user, hasTemporaryPassword: false);

        $mail->assertSeeInHtml('Falta só escolher o seu plano');
        $mail->assertSeeInHtml('Na primeira entrada, a plataforma mostra os planos. Escolha o seu para liberar o agendamento.');
        $mail->assertDontSeeInHtml('agora você tem acesso');
    });

    it('promises access to an employee whose company has a contract', function (): void {
        $company = Company::factory()->create();
        CompanyPlan::factory()->active()->create(['company_id' => $company->getKey()]);

        $mail = new EmployeeOnboardingMail(onboardingEmployeeOf($company), hasTemporaryPassword: true);

        $mail->assertSeeInHtml('agora você tem acesso a sessões individuais');
        $mail->assertDontSeeInHtml('escolher o seu plano');
        $mail->assertDontSeeInHtml('a plataforma mostra os planos');
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
    });

    it('promises no deadline for the first booking, a rule the system does not enforce', function (): void {
        $mail = new EmployeeOnboardingMail(User::factory()->create(), hasTemporaryPassword: true);

        $mail->assertDontSeeInHtml('inativado');
        $mail->assertDontSeeInHtml('prazo para fazer o seu primeiro agendamento');
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
