<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\App\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('shows the next upcoming appointment', function (): void {
    $consultant = Consultant::factory()->create(['name' => 'Dr. João Silva']);
    Appointment::factory()->withStatus(AppointmentStatus::Active)->create([
        'user_id' => $this->employee->id,
        'consultant_id' => $consultant->id,
        'appointment_at' => now()->addDays(3),
    ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Próxima consultoria')
        ->assertSee('Dr. João Silva');
});

it('ignores past appointments and shows the empty state', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->id,
        'appointment_at' => now()->subDays(3),
    ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Agende sua próxima consultoria');
});

it('links to the appointments list', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Active)->create([
        'user_id' => $this->employee->id,
        'appointment_at' => now()->addDays(2),
    ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Ver todos')
        ->assertSee(AppointmentResource::getUrl('index'));
});

it('exposes the cancel action on the upcoming appointment', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->id,
            'appointment_at' => now()->addHours(25),
        ]);

    livewire(NextAppointmentWidget::class)
        ->assertActionVisible('cancelAppointment');
});

it('shows a tooltip on the awaiting-confirmation button', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create([
            'user_id' => $this->employee->id,
            'appointment_at' => now()->addDays(2),
            'meeting_url' => null,
        ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Aguardando confirmação')
        ->assertSee(__('panel-app::widgets.next_appointment.awaiting_tooltip'));
});
