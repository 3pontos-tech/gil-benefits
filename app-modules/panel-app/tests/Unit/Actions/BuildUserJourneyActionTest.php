<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\App\Actions\BuildUserJourneyAction;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

it('builds a journey from the user anamnese and appointments', function (): void {
    $user = User::factory()->create();
    UserAnamnese::factory()->create(['user_id' => $user->id, 'life_moment' => LifeMoment::Saver]);

    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now()->subDays(14),
    ]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::InvestmentAdvisory,
        'appointment_at' => now()->subDays(2),
    ]);
    // Mesmo tema repetido não conta duas vezes
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now()->subDay(),
    ]);
    // Não-concluída não entra na contagem
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::RiskAndCompliance,
        'appointment_at' => now()->addDay(),
    ]);

    $journey = resolve(BuildUserJourneyAction::class)($user);

    expect($journey->stage)->toBe(LifeMoment::Saver)
        ->and($journey->stageIndex)->toBe(3) // Endebted,Messy,Payer,Saver
        ->and($journey->completedConsultations)->toBe(3)
        ->and($journey->topicsCovered)->toHaveCount(2)
        ->and($journey->topicsTotal)->toBe(6)
        ->and($journey->ratingsGiven)->toBe(0)
        ->and($journey->pendingRatings)->toBe(3); // 3 concluídas sem feedback
});

it('handles a user with no anamnese', function (): void {
    $user = User::factory()->create();

    $journey = resolve(BuildUserJourneyAction::class)($user);

    expect($journey->stage)->toBeNull()
        ->and($journey->stageIndex)->toBeNull()
        ->and($journey->isOnboarded())->toBeFalse()
        ->and($journey->completedConsultations)->toBe(0)
        ->and($journey->topicsCovered)->toBe([]);
});

it('counts ratings given by the user', function (): void {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $user->id]);
    AppointmentFeedback::factory()->create(['user_id' => $user->id, 'appointment_id' => $appointment->id, 'rating' => 5]);

    $journey = resolve(BuildUserJourneyAction::class)($user);

    expect($journey->ratingsGiven)->toBe(1)
        ->and($journey->pendingRatings)->toBe(0); // a única concluída já foi avaliada
});

it('counts completed consultations still pending a rating', function (): void {
    $user = User::factory()->create();

    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $user->id]);

    $rated = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $user->id]);
    AppointmentFeedback::factory()->create(['user_id' => $user->id, 'appointment_id' => $rated->id, 'rating' => 4]);

    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $user->id,
        'appointment_at' => now()->addDay(),
    ]);

    $journey = resolve(BuildUserJourneyAction::class)($user);

    expect($journey->pendingRatings)->toBe(1);
});

it('is scoped and memoizes the journey per user within a request', function (): void {
    $user = User::factory()->create();
    UserAnamnese::factory()->create(['user_id' => $user->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $user->id]);

    $action = resolve(BuildUserJourneyAction::class);

    // Binding scoped: o container devolve a mesma instância no mesmo request.
    expect($action)->toBe(resolve(BuildUserJourneyAction::class));

    $journeyQueries = 0;
    DB::listen(function ($query) use (&$journeyQueries): void {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'appointments') || str_contains($sql, 'appointment_feedbacks')) {
            ++$journeyQueries;
        }
    });

    $first = $action($user);
    $queriesAfterFirst = $journeyQueries;
    $second = $action($user);

    // A 2ª invocação vem do cache: mesma instância de DTO e nenhuma query nova.
    expect($second)->toBe($first)
        ->and($journeyQueries)->toBe($queriesAfterFirst);
});
