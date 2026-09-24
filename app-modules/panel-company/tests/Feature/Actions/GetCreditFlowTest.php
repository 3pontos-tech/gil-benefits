<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditFlow;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts only credits transferred within the window', function (): void {
    $company = Company::factory()->create();
    $holder = User::factory()->create();

    UserCredit::factory()->transferred()->count(2)->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(),
    ]);

    // Não transferido (transferred_at null): fora da contagem.
    UserCredit::factory()->available()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(),
    ]);

    // Transferido antes da janela de 12 meses: fora da contagem.
    UserCredit::factory()->transferred()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(), 'transferred_at' => now()->subMonths(15),
    ]);

    $flow = resolve(GetCreditFlow::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($flow->distributed)->toBe(2);
});

it('counts only credits used (with an appointment) within the window', function (): void {
    $company = Company::factory()->create();
    $holder = User::factory()->create();

    $inWindow = Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $holder->getKey(), 'appointment_at' => now(),
    ]);
    UserCredit::factory()->used()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(), 'appointment_id' => $inWindow->id,
    ]);

    // Used, mas com agendamento fora da janela: fora da contagem.
    $outOfWindow = Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $holder->getKey(), 'appointment_at' => now()->subMonths(15),
    ]);
    UserCredit::factory()->used()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(), 'appointment_id' => $outOfWindow->id,
    ]);

    $flow = resolve(GetCreditFlow::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($flow->usedInPeriod)->toBe(1);
});

it('restricts the flow to the filtered holder', function (): void {
    $company = Company::factory()->create();
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    UserCredit::factory()->transferred()->create([
        'company_id' => $company->id, 'holder_id' => $alice->getKey(),
    ]);
    UserCredit::factory()->transferred()->count(2)->create([
        'company_id' => $company->id, 'holder_id' => $bob->getKey(),
    ]);

    $flow = resolve(GetCreditFlow::class)->handle(
        $company,
        MetricsPeriod::lastMonths(12),
        new MetricsFilters(userId: (string) $alice->getKey()),
    );

    expect($flow->distributed)->toBe(1);
});
