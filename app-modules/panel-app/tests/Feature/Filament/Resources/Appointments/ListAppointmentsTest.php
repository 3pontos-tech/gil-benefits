<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('lists the beneficiary appointments regardless of the company they were booked under', function (): void {
    $otherCompany = Company::factory()->create();

    $ownTenant = Appointment::factory()->create(['user_id' => $this->employee->id]);
    $otherTenant = Appointment::factory()->create([
        'user_id' => $this->employee->id,
        'company_id' => $otherCompany->getKey(),
    ]);
    $noCompany = Appointment::factory()->create([
        'user_id' => $this->employee->id,
        'company_id' => null,
    ]);

    livewire(ListAppointments::class)
        ->assertCanSeeTableRecords([$ownTenant, $otherTenant, $noCompany]);
});

it('does not list soft-deleted appointments', function (): void {
    $deleted = Appointment::factory()->create(['user_id' => $this->employee->id]);
    $deleted->delete();

    livewire(ListAppointments::class)
        ->assertCanNotSeeTableRecords([$deleted]);
});

it('never lists appointments belonging to another user', function (): void {
    $stranger = Appointment::factory()->create(['user_id' => User::factory()->create()->getKey()]);

    livewire(ListAppointments::class)
        ->assertCanNotSeeTableRecords([$stranger]);
});
