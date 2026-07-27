<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('greets the user and renders the four indicator cards', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(2)->create(['user_id' => $this->employee->id]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.journey_hero.welcome'))
        ->assertSee($this->employee->name)
        ->assertSee(__('panel-app::widgets.journey_hero.completed_consultations'))
        ->assertSee(__('panel-app::widgets.journey_hero.topics_covered'))
        ->assertSee(__('panel-app::widgets.journey_hero.ratings_given'))
        ->assertSee(__('panel-app::widgets.journey_hero.financial_health'))
        ->assertSee('2'); // consultorias concluídas
});

it('shows the month-over-month delta only for indicators that moved', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(2)->create([
        'user_id' => $this->employee->id,
        'appointment_at' => now(),
    ]);

    // Duas consultorias neste mês e nenhuma avaliação: o card de avaliações
    // fica sem indicador, em vez de mostrar "+0".
    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSeeText('+2 ' . __('panel-app::widgets.journey_hero.this_month'))
        ->assertDontSeeText('+0 ' . __('panel-app::widgets.journey_hero.this_month'));
});

it('scores financial health out of one hundred', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        // Saver é o 4º de 5 estágios: 4/5 * 60 = 48, sem temas nem avaliações.
        ->assertSee('48')
        ->assertSee('/100');
});

it('shows an onboarding CTA when the user has no anamnese', function (): void {
    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.journey_hero.onboarding_cta'));
});
