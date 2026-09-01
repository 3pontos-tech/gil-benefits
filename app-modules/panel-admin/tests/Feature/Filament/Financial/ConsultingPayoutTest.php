<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingPayout;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ConsultingPayoutWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    config()->set('billing.consulting_cost_in_cents', 8000); // R$ 80,00
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

/**
 * Consultorias de um consultor no mês, uma por status pedido.
 *
 * @param  array<int, AppointmentStatus>  $statuses
 */
function payoutConsultant(string $name, array $statuses, ?int $ownCost = null): Consultant
{
    $consultant = Consultant::factory()->create([
        'name' => $name,
        'cost_per_appointment_cents' => $ownCost,
    ]);

    $company = Company::factory()->create();

    foreach ($statuses as $status) {
        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'consultant_id' => $consultant->getKey(),
            'status' => $status,
            'appointment_at' => now()->subDays(5),
        ]);
    }

    return $consultant;
}

describe('base do repasse', function (): void {
    it('conta realizadas, canceladas fora da regra e no-show', function (): void {
        payoutConsultant('Ana', [
            AppointmentStatus::Completed,
            AppointmentStatus::Completed,
            AppointmentStatus::CancelledLate,
            AppointmentStatus::NoShow,
        ]);

        $row = resolve(GetConsultingPayout::class)->handle($this->filters)->rows->first();

        expect($row->completed)->toBe(2)
            ->and($row->cancelledLate)->toBe(1)
            ->and($row->noShow)->toBe(1)
            ->and($row->billable())->toBe(4);
    });

    it('deixa fora o cancelamento dentro da regra, que devolve o crédito', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed, AppointmentStatus::Cancelled]);

        expect(resolve(GetConsultingPayout::class)->handle($this->filters)->rows->first()->billable())->toBe(1);
    });

    it('deixa fora agendamento que nunca foi confirmado', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed, AppointmentStatus::Pending]);

        expect(resolve(GetConsultingPayout::class)->handle($this->filters)->rows->first()->billable())->toBe(1);
    });

    it('ignora consultoria de outro mês', function (): void {
        $consultant = Consultant::factory()->create(['name' => 'Ana']);
        Appointment::factory()->create([
            'company_id' => Company::factory()->create()->getKey(),
            'consultant_id' => $consultant->getKey(),
            'status' => AppointmentStatus::Completed,
            'appointment_at' => now()->subMonths(2),
        ]);

        expect(resolve(GetConsultingPayout::class)->handle($this->filters)->rows)->toBeEmpty();
    });
});

describe('valor do repasse', function (): void {
    it('multiplica pelo custo padrão do sistema', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed, AppointmentStatus::NoShow]);

        $row = resolve(GetConsultingPayout::class)->handle($this->filters)->rows->first();

        expect($row->payoutCents())->toBe(16000)
            ->and($row->usesDefaultCost)->toBeTrue();
    });

    it('prefere o custo próprio do consultor', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed], ownCost: 12000);

        $row = resolve(GetConsultingPayout::class)->handle($this->filters)->rows->first();

        expect($row->payoutCents())->toBe(12000)
            ->and($row->usesDefaultCost)->toBeFalse();
    });

    it('soma o total do mês', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed, AppointmentStatus::Completed]);
        payoutConsultant('Bruno', [AppointmentStatus::CancelledLate], ownCost: 5000);

        $payout = resolve(GetConsultingPayout::class)->handle($this->filters);

        expect($payout->totalCents())->toBe(16000 + 5000)
            ->and($payout->billableAppointments())->toBe(3);
    });

    it('ordena do maior repasse para o menor', function (): void {
        payoutConsultant('Pouco', [AppointmentStatus::Completed]);
        payoutConsultant('Muito', [AppointmentStatus::Completed, AppointmentStatus::Completed, AppointmentStatus::NoShow]);

        $names = resolve(GetConsultingPayout::class)->handle($this->filters)->rows->pluck('consultantName')->all();

        expect($names[0])->toBe('Muito');
    });
});

describe('custo ausente', function (): void {
    it('devolve nulo em vez de zero quando não há custo nenhum', function (): void {
        config()->set('billing.consulting_cost_in_cents');
        payoutConsultant('Ana', [AppointmentStatus::Completed]);

        $payout = resolve(GetConsultingPayout::class)->handle($this->filters);

        expect($payout->rows->first()->payoutCents())->toBeNull()
            ->and($payout->isConfigured())->toBeFalse();
    });

    it('mantém o consultor sem custo fora do total e o denuncia', function (): void {
        config()->set('billing.consulting_cost_in_cents');
        payoutConsultant('Sem custo', [AppointmentStatus::Completed]);
        payoutConsultant('Com custo', [AppointmentStatus::Completed], ownCost: 9000);

        $payout = resolve(GetConsultingPayout::class)->handle($this->filters);

        expect($payout->totalCents())->toBe(9000)
            ->and($payout->withoutCost()->pluck('consultantName')->all())->toBe(['Sem custo']);
    });
});

describe('tela', function (): void {
    it('mostra o repasse por consultor', function (): void {
        payoutConsultant('Ana Souza', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingPayoutWidget::class)
            ->assertOk()
            ->assertSeeText('Ana Souza')
            ->assertSeeText('Consumiram crédito');
    });

    it('avisa quando o custo não está configurado', function (): void {
        config()->set('billing.consulting_cost_in_cents');
        payoutConsultant('Ana', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingPayoutWidget::class)
            ->assertOk()
            ->assertSeeText('Repasse por consultoria ainda não configurado');
    });

    it('não exibe margem, que a plataforma não sabe calcular', function (): void {
        payoutConsultant('Ana', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingPayoutWidget::class)
            ->assertOk()
            ->assertDontSeeText('Margem');
    });
});
