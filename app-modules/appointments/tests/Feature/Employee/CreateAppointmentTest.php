<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\App\Filament\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Facades\Zap;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $company = Company::factory()->create();
    $this->employee = User::factory()->employee()->create();
    $company->employees()->attach($this->employee);
    actingAs($this->employee);
    filament()->setTenant($company);
    filament()->setCurrentPanel(FilamentPanel::User->value);

    $consultant = Consultant::factory()->create();
    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from(today()->toDateString())
        ->to(today()->copy()->addMonth()->toDateString())
        ->addPeriod('00:00', '23:59')
        ->save();
});

it('should redirect if user has not a valid subscription', function (): void {
    livewire(CreateAppointment::class)
        ->assertStatus(302);
});

it('should redirect if user has an incoming appoitment', function (): void {
    $price = Price::factory()->state(['monthly_appointments' => 1])->create();

    $this->employee->subscriptions()->create([
        'stripe_id' => 'sub_' . uniqid(),
        'stripe_price' => $price->provider_price_id,
        'stripe_status' => 'active',
        'type' => BillableTypeEnum::User,
    ]);
    Appointment::factory()->state(['user_id' => $this->employee->getKey()])->withStatus(AppointmentStatus::Pending)->create();

    livewire(CreateAppointment::class)
        ->assertStatus(302)
        ->assertNotified('Não é possível agendar agora');
});

it('should be able to appointment', function (): void {
    $price = Price::factory()->state(['monthly_appointments' => 5])->create();

    $this->employee->subscriptions()->create([
        'stripe_id' => 'sub_' . uniqid(),
        'stripe_price' => $price->provider_price_id,
        'stripe_status' => 'active',
        'type' => BillableTypeEnum::User,
    ]);

    livewire(CreateAppointment::class)
        ->assertOk()
        ->assertWizardCurrentStep(1)
        ->fillForm([
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
        ])
        ->goToNextWizardStep()
        ->assertOk()
        ->assertWizardCurrentStep(2)
        ->fillForm([
            'date' => today()->format('Y-m-d'),
            'appointment_at' => today()->hourOfDay(8),
            'notes' => 'notes',
        ])
        ->goToNextWizardStep()
        ->assertOk()
        ->call('submit')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Appointment::class, [
        'user_id' => $this->employee->getKey(),
        'consultant_id' => null,
        'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
        'status' => AppointmentStatus::Pending->value,
        'notes' => 'notes',
    ]);
});
