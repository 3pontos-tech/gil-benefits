<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

function makeBookDto(User $user): BookAppointmentDTO
{
    return new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: Date::now()->addDays(3)->setTime(10, 0),
    );
}

// A user with no company/plan always has monthly_appointments_left = 0.
function userWithNoQuota(): User
{
    return User::factory()->create();
}

// A user with an active contractual plan that grants quota > 0 and no prior appointments.
function userWithQuota(int $limit = 2): User
{
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->create([
        'company_id' => $company->getKey(),
        'monthly_appointments_per_employee' => $limit,
    ]);
    $user = User::factory()->employee()->create();
    $company->employees()->attach($user->getKey());

    return $user;
}

it('consumes a credit when monthly quota is exhausted', function (): void {
    $user = userWithNoQuota();
    $credit = UserCredit::factory()->available()->create(['holder_id' => $user->getKey()]);

    resolve(BookAppointmentAction::class)->handle(makeBookDto($user));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('links the consumed credit to the created appointment', function (): void {
    $user = userWithNoQuota();
    $credit = UserCredit::factory()->available()->create(['holder_id' => $user->getKey()]);

    resolve(BookAppointmentAction::class)->handle(makeBookDto($user));

    $appointment = $user->appointments()->first();

    expect($credit->refresh()->appointment_id)->toBe($appointment->id);
});

it('consumes the first available credit when multiple exist', function (): void {
    $user = userWithNoQuota();
    $first = UserCredit::factory()->available()->create(['holder_id' => $user->getKey()]);
    $second = UserCredit::factory()->available()->create(['holder_id' => $user->getKey()]);

    resolve(BookAppointmentAction::class)->handle(makeBookDto($user));

    expect($first->refresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($second->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('does not consume a credit when monthly quota is still available', function (): void {
    $user = userWithQuota(2);
    $credit = UserCredit::factory()->available()->create(['holder_id' => $user->getKey()]);

    resolve(BookAppointmentAction::class)->handle(makeBookDto($user));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('does not fail when quota is exhausted and no credit exists', function (): void {
    $user = userWithNoQuota();

    expect(fn () => resolve(BookAppointmentAction::class)->handle(makeBookDto($user)))
        ->not->toThrow(Throwable::class);
});

it('creates the appointment regardless of credit availability', function (): void {
    $user = userWithNoQuota();

    resolve(BookAppointmentAction::class)->handle(makeBookDto($user));

    expect($user->appointments()->count())->toBeOne();
});
