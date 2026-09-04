<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Appointments\Pages\CreateAppointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();

    // No Bus/Event fake here: the CreditConsumed listener must actually run so we can
    // assert the credit is consumed end-to-end (queue is sync in the test environment).
    $this->createViaAdmin = function (User $user): void {
        livewire(CreateAppointment::class)
            ->fillForm([
                'user_id' => $user->id,
                'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
                'appointment_at' => Date::now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    };
});

it('consumes a credit when the user has no monthly quota', function (): void {
    // Employed by a company with no plan: monthly_appointments_left = 0.
    $company = Company::factory()->create();
    $user = User::factory()->employee()->create();
    $company->employees()->attach($user->getKey());

    $credit = UserCredit::factory()->available()->create([
        'holder_id' => $user->getKey(),
        'company_id' => $company->getKey(),
    ]);

    ($this->createViaAdmin)($user);

    $appointment = $user->appointments()->firstOrFail();

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($credit->appointment_id)->toBe($appointment->id);
});

it('does not consume a credit when the user still has monthly quota', function (): void {
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->create([
        'company_id' => $company->getKey(),
        'monthly_appointments_per_employee' => 2,
    ]);
    $user = User::factory()->employee()->create();
    $company->employees()->attach($user->getKey());

    $credit = UserCredit::factory()->available()->create([
        'holder_id' => $user->getKey(),
        'company_id' => $company->getKey(),
    ]);

    ($this->createViaAdmin)($user);

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});
