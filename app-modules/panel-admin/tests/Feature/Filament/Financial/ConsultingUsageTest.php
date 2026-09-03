<?php

declare(strict_types=1);

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Enums\CreditOrderStatusEnum;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Billing\Core\Models\CreditOrder;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\GetConsultingVolume;
use TresPontosTech\PanelAdmin\Actions\Financial\GetExtraCredits;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\ConsultingUsage;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ConsultingVolumeWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\ExtraCreditsTableWidget;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    Cache::flush();
    travelTo(CarbonImmutable::create(2026, 8, 27, 12, 0, 0));
    $this->filters = FinancialFilters::fromPageFilters(null);
    actingAsFinancial();
});

function appointmentWith(AppointmentStatus $status, ?Company $company = null, ?CarbonImmutable $at = null): Appointment
{
    $company ??= Company::factory()->create();

    return Appointment::factory()->create([
        'company_id' => $company->getKey(),
        'status' => $status,
        'appointment_at' => $at ?? now()->subDays(5),
    ]);
}

describe('volume de consultorias', function (): void {
    it('conta cada desfecho no seu card', function (): void {
        appointmentWith(AppointmentStatus::Completed);
        appointmentWith(AppointmentStatus::Completed);
        appointmentWith(AppointmentStatus::Cancelled);
        appointmentWith(AppointmentStatus::NoShow);
        appointmentWith(AppointmentStatus::Pending);

        $volume = resolve(GetConsultingVolume::class)->handle($this->filters);

        expect($volume->scheduled)->toBe(5)
            ->and($volume->completed)->toBe(2)
            ->and($volume->cancelled)->toBe(1)
            ->and($volume->noShow)->toBe(1);
    });

    it('soma cancelamento tardio em canceladas, sem perder a contagem', function (): void {
        appointmentWith(AppointmentStatus::Cancelled);
        appointmentWith(AppointmentStatus::CancelledLate);
        appointmentWith(AppointmentStatus::CancelledLate);

        $volume = resolve(GetConsultingVolume::class)->handle($this->filters);

        expect($volume->cancelled)->toBe(3)
            ->and($volume->cancelledLate)->toBe(2);
    });

    it('calcula a taxa de realização sobre as agendadas', function (): void {
        appointmentWith(AppointmentStatus::Completed);
        appointmentWith(AppointmentStatus::Completed);
        appointmentWith(AppointmentStatus::Cancelled);
        appointmentWith(AppointmentStatus::NoShow);

        expect(resolve(GetConsultingVolume::class)->handle($this->filters)->completionRate())->toBe(50.0);
    });

    it('não inventa taxa quando não houve agendamento', function (): void {
        $volume = resolve(GetConsultingVolume::class)->handle($this->filters);

        expect($volume->completionRate())->toBeNull()
            ->and($volume->isEmpty())->toBeTrue();
    });

    it('ignora consultoria de outro mês', function (): void {
        appointmentWith(AppointmentStatus::Completed, at: now()->toImmutable()->subMonths(2));

        expect(resolve(GetConsultingVolume::class)->handle($this->filters)->scheduled)->toBe(0);
    });

    it('filtra por empresa', function (): void {
        $alvo = Company::factory()->create();
        appointmentWith(AppointmentStatus::Completed, $alvo);
        appointmentWith(AppointmentStatus::Completed);

        $filtrado = FinancialFilters::fromPageFilters(['companies' => [$alvo->getKey()]]);

        expect(resolve(GetConsultingVolume::class)->handle($filtrado)->scheduled)->toBe(1);
    });
});

describe('créditos por origem', function (): void {
    it('separa plano, comprado e cortesia', function (): void {
        $company = Company::factory()->create(['name' => 'Alpha SA']);
        $holder = User::factory()->create();

        $order = CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'quantity' => 2,
            'amount_cents' => 30000,
            'paid_at' => now()->subDays(10),
        ]);

        $grant = CreditGrant::factory()->create([
            'company_id' => $company->getKey(),
            'quantity' => 1,
        ]);

        foreach ([['plan', null, null], ['order', $order->getKey(), null], ['grant', null, $grant->getKey()]] as [$kind, $orderId, $grantId]) {
            $appointment = appointmentWith(AppointmentStatus::Completed, $company);

            UserCredit::factory()->create([
                'company_id' => $company->getKey(),
                'owner_id' => $holder->getKey(),
                'holder_id' => $holder->getKey(),
                'appointment_id' => $appointment->getKey(),
                'credit_order_id' => $orderId,
                'grant_id' => $grantId,
            ]);
        }

        $row = resolve(GetExtraCredits::class)->handle($this->filters)->first();

        expect($row->fromPlan)->toBe(1)
            ->and($row->purchased)->toBe(1)
            ->and($row->granted)->toBe(1)
            ->and($row->total())->toBe(3);
    });

    it('valoriza o crédito comprado pela fatia do pedido, não pelo total', function (): void {
        $company = Company::factory()->create(['name' => 'Alpha SA']);
        $holder = User::factory()->create();

        $order = CreditOrder::factory()->create([
            'company_id' => $company->getKey(),
            'provider' => BillingProviderEnum::Virtu,
            'status' => CreditOrderStatusEnum::Paid,
            'quantity' => 3,
            'amount_cents' => 30000,
            'paid_at' => now()->subDays(10),
        ]);

        $appointment = appointmentWith(AppointmentStatus::Completed, $company);
        UserCredit::factory()->create([
            'company_id' => $company->getKey(),
            'owner_id' => $holder->getKey(),
            'holder_id' => $holder->getKey(),
            'appointment_id' => $appointment->getKey(),
            'credit_order_id' => $order->getKey(),
        ]);

        expect(resolve(GetExtraCredits::class)->handle($this->filters)->first()->purchasedValueCents)->toBe(10000);
    });

    it('não cobra pela cortesia', function (): void {
        $company = Company::factory()->create(['name' => 'Alpha SA']);
        $holder = User::factory()->create();
        $grant = CreditGrant::factory()->create(['company_id' => $company->getKey(), 'quantity' => 1]);

        $appointment = appointmentWith(AppointmentStatus::Completed, $company);
        UserCredit::factory()->create([
            'company_id' => $company->getKey(),
            'owner_id' => $holder->getKey(),
            'holder_id' => $holder->getKey(),
            'appointment_id' => $appointment->getKey(),
            'grant_id' => $grant->getKey(),
        ]);

        $row = resolve(GetExtraCredits::class)->handle($this->filters)->first();

        expect($row->granted)->toBe(1)
            ->and($row->purchasedValueCents)->toBe(0);
    });

    it('ignora crédito ainda não consumido', function (): void {
        $company = Company::factory()->create(['name' => 'Alpha SA']);
        $holder = User::factory()->create();

        UserCredit::factory()->create([
            'company_id' => $company->getKey(),
            'owner_id' => $holder->getKey(),
            'holder_id' => $holder->getKey(),
            'appointment_id' => null,
        ]);

        expect(resolve(GetExtraCredits::class)->handle($this->filters))->toBeEmpty();
    });
});

describe('tela', function (): void {
    it('mostra os cards de volume', function (): void {
        appointmentWith(AppointmentStatus::Completed);

        Livewire::test(ConsultingVolumeWidget::class)
            ->assertOk()
            ->assertSeeText('Agendadas')
            ->assertSeeText('No-show')
            ->assertSeeText('Taxa de realização');
    });

    it('avisa quando há cancelamento tardio, que consome crédito', function (): void {
        appointmentWith(AppointmentStatus::CancelledLate);

        Livewire::test(ConsultingVolumeWidget::class)
            ->assertOk()
            ->assertSeeText('consome crédito');
    });

    it('mostra o estado vazio dos créditos com o texto da story', function (): void {
        Livewire::test(ExtraCreditsTableWidget::class)
            ->assertOk()
            ->assertSeeText('Nenhum crédito extra utilizado neste período');
    });

    it('monta os dois widgets na página', function (): void {
        Livewire::test(ConsultingUsage::class)
            ->assertOk()
            ->assertSeeLivewire(ConsultingVolumeWidget::class)
            ->assertSeeLivewire(ExtraCreditsTableWidget::class);
    });

    it('fecha para Admin comum', function (): void {
        actingAsAdmin();

        expect(ConsultingUsage::canAccess())->toBeFalse();
    });
});
