<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('renders the hero with the journey momentum', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(2)->create(['user_id' => $this->employee->id]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee('Sua jornada financeira')
        ->assertSee('2') // consultorias concluídas
        ->assertSee('etapa 4 de 5'); // resumo mobile do progresso (Saver = índice 3)
});

it('shows an onboarding CTA when the user has no anamnese', function (): void {
    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee('Complete sua anamnese');
});

it('shows the pending-review banner when a completed consultation has no feedback', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $this->employee->id]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee('aguardando sua avaliação')
        ->assertSee('Avaliar agora');
});

it('hides the pending-review banner when every consultation is rated', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $this->employee->id]);
    AppointmentFeedback::factory()->create([
        'user_id' => $this->employee->id,
        'appointment_id' => $appointment->id,
        'rating' => 5,
    ]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertDontSee('aguardando sua avaliação');
});
