<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Support\Models\SupportTicket;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Erros fora do 422 em pt_BR e sem detalhe interno (2.5).
 */
it('answers 404 without the model class name', function (Closure $request): void {
    $employee = actingAsApiEmployee();

    $response = $request($employee)->assertNotFound()->assertExactJson(['message' => 'Não encontrado.']);

    expect($response->getContent())->not->toContain('TresPontosTech')->not->toContain('No query results');
})->with([
    'unknown appointment' => [fn (User $e) => getJson(route('api.v1.appointments.show', Str::uuid()))],
    'appointment of another person' => [fn (User $e) => getJson(route('api.v1.appointments.show', appointmentFor(User::factory()->create(), AppointmentStatus::Pending, now()->addDays(3))))],
    'unknown material' => [fn (User $e) => deleteJson(route('api.v1.materials.destroy', Str::uuid()))],
    'unknown ticket' => [fn (User $e) => patchJson(route('api.v1.tickets.update', Str::uuid()), ['status' => 'closed'])],
    'unknown notification' => [fn (User $e) => patchJson(route('api.v1.notifications.read', Str::uuid()))],
    'id that is not a uuid' => [fn (User $e) => getJson('/api/v1/appointments/123')],
    'route that does not exist' => [fn (User $e) => getJson('/api/v1/nada-aqui')],
]);

it('answers 404 in JSON even when the client did not ask for it', function (): void {
    get('/api/v1/nada-aqui')->assertNotFound()->assertExactJson(['message' => 'Não encontrado.']);
});

it('answers 405 in Portuguese', function (): void {
    actingAsApiEmployee();

    putJson(route('api.v1.credits.show'))
        ->assertMethodNotAllowed()
        ->assertExactJson(['message' => 'Esta operação não existe para este endereço.']);
});

it('answers 429 in Portuguese and keeps Retry-After', function (): void {
    RateLimiter::clear('api-login');
    config()->set('auth.timebox_duration', 0);

    foreach (range(1, 5) as $attempt) {
        postJson(route('api.v1.auth.login'), ['email' => 'ana@empresa.com', 'password' => 'x', 'device_name' => 'Android']);
    }

    postJson(route('api.v1.auth.login'), ['email' => 'ana@empresa.com', 'password' => 'x', 'device_name' => 'Android'])
        ->assertTooManyRequests()
        ->assertExactJson(['message' => 'Muitas tentativas. Aguarde um instante e tente de novo.'])
        ->assertHeader('Retry-After');
});

it('answers a token without the employee ability with the no-access message', function (): void {
    $employee = actingAsApiEmployee();
    Sanctum::actingAs($employee, ['company']);

    getJson(route('api.v1.me.show'))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Esta conta não tem acesso ao aplicativo.']);
});

it('keeps the reason of a 403 raised by the app rules', function (): void {
    $user = registeredWithoutPlan();
    Sanctum::actingAs($user, [TokenAbility::Employee->value]);

    getJson(route('api.v1.me.show'))
        ->assertForbidden()
        ->assertExactJson(['message' => 'Para usar o app, ative um plano pelo site.']);
});

it('leaves validation errors and 401 as they were', function (): void {
    getJson(route('api.v1.me.show'))->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);

    actingAsApiEmployee();

    postJson(route('api.v1.tickets.store'), [])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['category', 'subject', 'description']]);
});

function registeredWithoutPlan(): User
{
    return defaultCompanyUser();
}

describe('domain refusals', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-10-07 10:00:00');
    });

    it('answers a booking refused for two reasons in the format Laravel gives a ValidationException', function (): void {
        $employee = actingAsApiEmployee();
        appointmentFor($employee, AppointmentStatus::Pending, now()->addDays(3));
        consultantAvailableOn(Date::parse('2026-10-09'));

        $response = postJson(route('api.v1.appointments.store'), ['category_type' => 'personal_finance', 'appointment_at' => '2026-10-09T09:00:00-03:00'])
            ->assertUnprocessable();

        $reasons = $response->json('errors.credit');

        expect($reasons)->toHaveCount(2)
            ->and($response->json('message'))->toBe(ValidationException::withMessages(['credit' => $reasons])->getMessage());
    });

    it('does not report the refusals of the app as errors', function (): void {
        Exceptions::fake();
        $employee = actingAsApiEmployee();
        appointmentFor($employee, AppointmentStatus::Pending, now()->addDays(3));

        postJson(route('api.v1.appointments.store'), ['category_type' => 'personal_finance', 'appointment_at' => '2026-10-09T09:00:00-03:00'])
            ->assertUnprocessable();
        patchJson(route('api.v1.tickets.update', SupportTicket::factory()->create(['user_id' => $employee->id, 'status' => 'closed'])), ['status' => 'closed'])
            ->assertUnprocessable();

        Exceptions::assertNothingReported();
    });

    it('leaves the same exception alone outside the employee API', function (): void {
        Exceptions::fake();
        Route::get('_probe-refusal', fn () => throw new SlotUnavailableException);

        getJson('/_probe-refusal')->assertServerError();

        Exceptions::assertReported(SlotUnavailableException::class);
    });
});
