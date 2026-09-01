<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingValue;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ConsultingValueWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    config()->set('billing.consulting_value_in_cents', 8000); // R$ 80,00
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

/**
 * Uma empresa com consultorias no mês, uma por status pedido.
 *
 * @param  array<int, AppointmentStatus>  $statuses
 */
function companyWithAppointments(string $name, array $statuses, ?int $seats = null): Company
{
    $company = Company::factory()->create(['name' => $name]);

    if ($seats !== null) {
        $company->subscriptions()->create([
            'type' => 'company',
            'stripe_id' => 'sub_' . fake()->unique()->uuid(),
            'stripe_status' => 'active',
            'quantity' => $seats,
        ]);
    }

    $consultant = Consultant::factory()->create();

    foreach ($statuses as $status) {
        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'consultant_id' => $consultant->getKey(),
            'status' => $status,
            'appointment_at' => now()->subDays(5),
        ]);
    }

    return $company;
}

describe('base do cálculo', function (): void {
    it('conta realizadas, canceladas fora da regra e no-show', function (): void {
        companyWithAppointments('Alpha SA', [
            AppointmentStatus::Completed,
            AppointmentStatus::Completed,
            AppointmentStatus::CancelledLate,
            AppointmentStatus::NoShow,
        ]);

        $row = resolve(GetConsultingValue::class)->handle($this->filters)->rows->first();

        expect($row->completed)->toBe(2)
            ->and($row->cancelledLate)->toBe(1)
            ->and($row->noShow)->toBe(1)
            ->and($row->billable())->toBe(4);
    });

    it('deixa fora o cancelamento dentro da regra, que devolve o crédito', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed, AppointmentStatus::Cancelled]);

        expect(resolve(GetConsultingValue::class)->handle($this->filters)->rows->first()->billable())->toBe(1);
    });

    it('deixa fora agendamento que nunca foi confirmado', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed, AppointmentStatus::Pending]);

        expect(resolve(GetConsultingValue::class)->handle($this->filters)->rows->first()->billable())->toBe(1);
    });

    it('ignora consultoria de outro mês', function (): void {
        $company = Company::factory()->create();
        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'consultant_id' => Consultant::factory()->create()->getKey(),
            'status' => AppointmentStatus::Completed,
            'appointment_at' => now()->subMonths(2),
        ]);

        expect(resolve(GetConsultingValue::class)->handle($this->filters)->rows)->toBeEmpty();
    });
});

describe('valor consumido', function (): void {
    it('multiplica o volume pelo valor da consultoria', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed, AppointmentStatus::NoShow]);

        expect(resolve(GetConsultingValue::class)->handle($this->filters)->rows->first()->valueCents())->toBe(16000);
    });

    it('soma o total do mês', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed, AppointmentStatus::Completed]);
        companyWithAppointments('Beta ME', [AppointmentStatus::CancelledLate]);

        $value = resolve(GetConsultingValue::class)->handle($this->filters);

        expect($value->totalCents())->toBe(24000)
            ->and($value->billableAppointments())->toBe(3);
    });

    it('ordena da empresa que mais consumiu para a que menos consumiu', function (): void {
        companyWithAppointments('Pouco', [AppointmentStatus::Completed]);
        companyWithAppointments('Muito', [
            AppointmentStatus::Completed,
            AppointmentStatus::Completed,
            AppointmentStatus::NoShow,
        ]);

        $names = resolve(GetConsultingValue::class)->handle($this->filters)->rows->pluck('companyName')->all();

        expect($names[0])->toBe('Muito');
    });
});

describe('mensalidade ao lado do consumo', function (): void {
    it('traz a mensalidade da empresa, com a mesma conta da listagem de contratos', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed], seats: 10);

        $row = resolve(GetConsultingValue::class)->handle($this->filters)->rows->first();

        expect($row->monthlyValue->isKnown())->toBeTrue()
            ->and($row->monthlyValue->cents)->toBe(44900);
    });

    it('marca como desconhecida a empresa sem valor, e nunca como zero', function (): void {
        companyWithAppointments('Sem contrato', [AppointmentStatus::Completed]);

        $value = resolve(GetConsultingValue::class)->handle($this->filters);

        expect($value->rows->first()->monthlyValue->isKnown())->toBeFalse()
            ->and($value->rows->first()->monthlyValue->cents)->toBeNull()
            ->and($value->withoutMonthlyValue())->toHaveCount(1);
    });
});

describe('valor não configurado', function (): void {
    it('devolve nulo em vez de zero', function (): void {
        config()->set('billing.consulting_value_in_cents');
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed]);

        $value = resolve(GetConsultingValue::class)->handle($this->filters);

        expect($value->rows->first()->valueCents())->toBeNull()
            ->and($value->totalCents())->toBeNull()
            ->and($value->isConfigured())->toBeFalse();
    });

    it('mantém o volume visível mesmo sem valor', function (): void {
        config()->set('billing.consulting_value_in_cents');
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed, AppointmentStatus::NoShow]);

        expect(resolve(GetConsultingValue::class)->handle($this->filters)->billableAppointments())->toBe(2);
    });
});

describe('tela', function (): void {
    it('mostra o consumo por empresa ao lado da mensalidade', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed], seats: 10);

        Livewire::test(ConsultingValueWidget::class)
            ->assertOk()
            ->assertSeeText('Alpha SA')
            ->assertSeeText('Consumiram crédito')
            ->assertSeeText('Mensalidade');
    });

    it('avisa quando o valor não está configurado', function (): void {
        config()->set('billing.consulting_value_in_cents');
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingValueWidget::class)
            ->assertOk()
            ->assertSeeText('Valor da consultoria ainda não configurado');
    });

    it('não exibe margem, que a plataforma não sabe calcular', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingValueWidget::class)
            ->assertOk()
            ->assertDontSeeText('Margem');
    });

    it('não fala em repasse, que é outro número', function (): void {
        companyWithAppointments('Alpha SA', [AppointmentStatus::Completed]);

        Livewire::test(ConsultingValueWidget::class)
            ->assertOk()
            ->assertDontSeeText('Repasse');
    });
});
