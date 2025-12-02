<?php

use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Filament\App\Resources\Appointments\Pages\CreateAppointment;
use TresPontosTech\Company\Models\Company;

uses(TestCase::class, RefreshDatabase::class);

describe('Appointment Booking Feature', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['active' => true]);
    });

    it('can access appointment creation page when authenticated', function () {
        $this->actingAs($this->user);

        $response = $this->get('/app/appointments/create');

        $response->assertSuccessful();
    });

    it('redirects unauthenticated users from appointment creation', function () {
        $response = $this->get('/app/appointments/create');

        $response->assertRedirect();
    });

    it('can create an appointment with valid data', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
            'notes' => 'Test appointment notes',
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appointments', [
            'user_id' => $this->user->id,
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'status' => AppointmentStatus::Pending->value,
        ]);
    });

    it('validates required appointment fields', function () {
        $this->actingAs($this->user);

        Livewire::test(CreateAppointment::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'category_type' => 'required',
                'appointment_at' => 'required',
            ]);
    });

    it('validates appointment date is in the future', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasFormErrors(['appointment_at']);
    });

    it('validates appointment category is valid', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => 'invalid_category',
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasFormErrors(['category_type']);
    });

    it('handles different appointment categories', function () {
        $this->actingAs($this->user);

        $categories = [
            AppointmentCategoryEnum::PersonalFinance,
            AppointmentCategoryEnum::InvestmentAdvisory,
            AppointmentCategoryEnum::TaxPlanning,
        ];

        foreach ($categories as $category) {
            $appointmentData = [
                'category_type' => $category->value,
                'appointment_at' => Carbon::now()->addDays(rand(1, 7))->format('Y-m-d H:i:s'),
            ];

            Livewire::test(CreateAppointment::class)
                ->fillForm($appointmentData)
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertDatabaseHas('appointments', [
                'user_id' => $this->user->id,
                'category_type' => $category->value,
            ]);
        }
    });

    it('shows success notification after booking', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertNotified();
    });

    it('can create appointment without notes', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
            // notes is optional
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appointments', [
            'user_id' => $this->user->id,
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
        ]);
    });

    it('sets appointment status to pending by default', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasNoFormErrors();

        $appointment = $this->user->appointments()->first();
        expect($appointment->status)->toBe(AppointmentStatus::Pending);
    });

    it('associates appointment with authenticated user', function () {
        $this->actingAs($this->user);

        $appointmentData = [
            'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
            'appointment_at' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
        ];

        Livewire::test(CreateAppointment::class)
            ->fillForm($appointmentData)
            ->call('create')
            ->assertHasNoFormErrors();

        $appointment = $this->user->appointments()->first();
        expect($appointment->user_id)->toBe($this->user->id);
    });
});
