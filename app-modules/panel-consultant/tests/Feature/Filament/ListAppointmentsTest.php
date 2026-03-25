<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();
});
it('should render', function (): void {
    livewire(ListAppointments::class)
        ->assertOk();
});

it("should render only consultant's appointments", function (): void {
    $anotherAppointments = Appointment::factory()->count(10)->create();
    livewire(ListAppointments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->consultant->appointments)
        ->assertCanNotSeeTableRecords($anotherAppointments);
});

test('normal users can not see consultant dashboard', function (): void {
    actingAs(User::factory()->createOne());

    get(route('filament.consultant.pages.consultant-dashboard'))
        ->assertForbidden();
});
